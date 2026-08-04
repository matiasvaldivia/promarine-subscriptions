<!doctype html>
<html lang="es">
<body style="margin:0;background:#eef5f7;font-family:Arial,Helvetica,sans-serif;color:#102436">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5f7;padding:24px 12px"><tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:20px;overflow:hidden">
        <tr><td style="padding:26px 30px;background:#020c19"><img src="{{ url('/assets/promarine/optimized/promarine-logo-300.webp') }}" width="180" alt="Promarine" style="display:block;max-width:180px;height:auto"></td></tr>
        <tr><td style="padding:32px 30px;text-align:center">
            <p style="margin:0 0 10px;color:#149baa;font-size:12px;font-weight:bold;letter-spacing:1.5px">MI PROMARINE</p>
            <h1 style="margin:0 0 14px;font-size:28px;line-height:1.2">Tu código de acceso</h1>
            <p style="margin:0 auto 24px;max-width:450px;color:#506675;line-height:1.6">Usá este código para ver tu plan y el calendario de próximos pagos y entregas.</p>
            <div style="display:inline-block;padding:16px 26px;border:1px solid #b7dce1;border-radius:14px;background:#f3fbfc;color:#061827;font-size:34px;font-weight:bold;letter-spacing:9px">{{ $code }}</div>
            <p style="margin:24px 0 0;color:#607783;font-size:13px">Vence en {{ $expiresInMinutes }} minutos y solo puede usarse una vez.</p>
            <p style="margin:22px 0 0;padding:14px 16px;border-radius:12px;background:#fff6dc;color:#69500c;font-size:13px;line-height:1.5"><b>Seguridad:</b> si no pediste este código, ignorá el mensaje. Nunca te vamos a pedir que lo compartas.</p>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
