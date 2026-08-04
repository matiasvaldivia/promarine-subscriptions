<!doctype html>
<html lang="es">
<body style="margin:0;background:#eef5f7;font-family:Arial,Helvetica,sans-serif;color:#102436">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5f7;padding:24px 12px"><tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:20px;overflow:hidden">
        <tr><td style="padding:26px 30px;background:#020c19"><img src="{{ url('/assets/promarine/optimized/promarine-logo-300.webp') }}" width="180" alt="Promarine" style="display:block;max-width:180px;height:auto"></td></tr>
        <tr><td style="padding:32px 30px">
            <p style="margin:0 0 10px;color:#149baa;font-size:12px;font-weight:bold;letter-spacing:1.5px">PAGO SIMULADO APROBADO</p>
            <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2">¡Hola, {{ $customer->name }}!</h1>
            <p style="margin:0 0 24px;color:#506675;line-height:1.6">Tu recorrido de compra Promarine finalizó correctamente. Esta confirmación es de demostración: no se efectuó ningún cobro ni se creó un pedido comercial real.</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #dbe8ec;border-radius:14px">
                <tr><td style="padding:18px"><b>{{ $subscription->plan->variant->product->name }}</b><br><span style="color:#607783">{{ $subscription->plan->variant->name }} · {{ $subscription->plan->name }}</span></td><td align="right" style="padding:18px;font-size:20px;font-weight:bold">$ {{ number_format((float) $subscription->amount, 0, ',', '.') }}</td></tr>
                <tr><td colspan="2" style="padding:0 18px 18px;color:#607783">Entrega simulada cada {{ $subscription->frequency }} días</td></tr>
            </table>
            <p style="margin:24px 0 8px"><b>Pedido:</b> {{ $order?->shopify_order_id }}</p>
            <p style="margin:0 0 24px"><b>Pago:</b> {{ $payment->provider_payment_id }}</p>
            <p style="margin:0 0 24px;text-align:center"><a href="{{ route('customer.portal.request') }}" style="display:inline-block;padding:14px 22px;border-radius:999px;background:#0c8797;color:#ffffff;text-decoration:none;font-weight:bold">Ver mi plan y calendario</a></p>
            <p style="margin:0;padding:14px 16px;border-radius:12px;background:#fff6dc;color:#69500c;font-size:13px;line-height:1.5"><b>Importante:</b> este email pertenece a una simulación local de Promarine y no representa una operación financiera real.</p>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
