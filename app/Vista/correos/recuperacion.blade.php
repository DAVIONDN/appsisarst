{{--
    Capa VISTA - Correo del enlace de recuperacion (HU-17).
    Se escribe con estilos en linea porque los clientes de correo no
    aplican hojas de estilo externas ni la mayoria de las reglas CSS
    modernas.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recuperacion de contrasena - SISARST</title>
</head>
<body style="margin:0;padding:24px;background:#f2f5f9;font-family:'Segoe UI',Arial,sans-serif;color:#212529">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto">
    <tr>
        <td style="background:#0b2d4d;padding:20px 24px;border-radius:8px 8px 0 0">
            <div style="color:#fff;font-size:22px;font-weight:bold;letter-spacing:.08em">SISARST</div>
            <div style="color:rgba(255,255,255,.7);font-size:12px">Red de Salud Tayacaja</div>
        </td>
    </tr>

    <tr>
        <td style="background:#fff;padding:24px;border:1px solid #e3e8ef;border-top:0">

            <h1 style="font-size:18px;margin:0 0 16px">Recuperacion de contrasena</h1>

            <p style="font-size:14px;line-height:1.6;margin:0 0 12px">
                Hola {{ $usuario->personal?->nombres ?? $usuario->username }}:
            </p>

            <p style="font-size:14px;line-height:1.6;margin:0 0 20px">
                Recibimos una solicitud para restablecer la contrasena de la cuenta
                <strong>{{ $usuario->username }}</strong>. Pulse el boton para definir
                una contrasena nueva.
            </p>

            <p style="text-align:center;margin:0 0 20px">
                <a href="{{ $enlace }}"
                   style="display:inline-block;background:#0d5aa7;color:#fff;text-decoration:none;
                          padding:12px 28px;border-radius:6px;font-size:15px;font-weight:600">
                    Restablecer mi contrasena
                </a>
            </p>

            <p style="font-size:13px;line-height:1.6;margin:0 0 12px;padding:12px;
                      background:#fff8e1;border-left:4px solid #ffc107">
                Este enlace es de <strong>un solo uso</strong> y vence en
                <strong>{{ $minutos }} minutos</strong>.
            </p>

            <p style="font-size:13px;line-height:1.6;color:#6c757d;margin:0 0 12px">
                Si el boton no funciona, copie y pegue esta direccion en su navegador:
            </p>
            <p style="font-size:12px;line-height:1.5;word-break:break-all;color:#0d5aa7;margin:0 0 20px">
                {{ $enlace }}
            </p>

            <p style="font-size:13px;line-height:1.6;color:#6c757d;margin:0">
                <strong>Si usted no solicito este cambio</strong>, ignore este mensaje: su
                contrasena actual sigue siendo valida y nadie mas puede usar este enlace sin
                acceso a su correo.
            </p>
        </td>
    </tr>

    <tr>
        <td style="background:#f8f9fa;padding:16px 24px;border:1px solid #e3e8ef;border-top:0;
                   border-radius:0 0 8px 8px;font-size:11px;color:#6c757d;text-align:center">
            Mensaje automatico del sistema SISARST. No responda a este correo.<br>
            Ley N.° 29733 de Proteccion de Datos Personales.
        </td>
    </tr>
</table>

</body>
</html>
