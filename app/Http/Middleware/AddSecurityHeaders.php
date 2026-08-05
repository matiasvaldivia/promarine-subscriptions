<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que agrega headers de seguridad a las responses.
 *
 *   - Content-Security-Policy (CSP): mitiga XSS limitando sources
 *   - Strict-Transport-Security (HSTS): fuerza HTTPS en navegadores
 *   - Permissions-Policy: deshabilita features del browser no usadas
 *
 * Aplicar globalmente. Si en el futuro se necesitan politicas distintas
 * para areas (admin vs public), se puede parametrizar via constructor.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // CSP: default-src 'self' permite recursos del mismo origen.
        // Se necesita 'unsafe-inline' para los estilos inline de los componentes
        // legacy (.pm-*) y los scripts inline del theme manager. Considerar
        // migrar a nonces/hashes en una iteracion futura.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.mercadopago.com https://*.mercadopago.com; ".
            "style-src 'self' 'unsafe-inline'; ".
            "img-src 'self' data: https:; ".
            "font-src 'self' data:; ".
            "connect-src 'self' https://api.mercadopago.com https://*.mercadopago.com; ".
            "frame-src 'self' https://www.mercadopago.com https://*.mercadopago.com; ".
            "object-src 'none'; ".
            "base-uri 'self'; ".
            "form-action 'self'"
        );

        // HSTS: solo en HTTPS. max-age 1 ano + subdominios.
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Permissions-Policy: deshabilita features del browser que la app no usa.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        return $response;
    }
}
