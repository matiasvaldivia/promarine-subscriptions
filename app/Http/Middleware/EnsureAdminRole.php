<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si no se especifican roles, permite el acceso si el usuario es tamara o posee algún rol.
        // Si no posee ningún rol, rechaza con 403.
        if (empty($roles)) {
            if ($user->username === 'tamara' || $user->roles()->exists()) {
                return $next($request);
            }
            abort(403, 'No tenés permisos suficientes para acceder a esta sección.');
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'No tenés permisos suficientes para acceder a esta sección.');
    }
}
