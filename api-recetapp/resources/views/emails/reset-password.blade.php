<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Restablecer Contraseña - RecetAPP</title>
</head>

<body
    style="background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 40px 20px; color: #212529;">

    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #e9ecef;">

        <!-- Cabecera -->
        <div style="background-color: #0d6efd; padding: 25px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 1px;">
                🍳 RecetAPP
            </h1>
        </div>

        <!-- Contenido -->
        <div style="padding: 35px 30px;">
            <h2 style="margin-top: 0; color: #dc3545; font-size: 22px;">Restablecer contraseña</h2>

            <p style="font-size: 16px; line-height: 1.6; color: #495057;">
                Has solicitado restablecer tu contraseña. Haz clic en el botón de abajo para crear una nueva:
            </p>

            <!-- Botón de acción -->
            <div style="text-align: center; margin: 35px 0 10px;">
                <a href="{{ $resetUrl }}"
                    style="display: inline-block; padding: 14px 28px; background-color: #dc3545; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">
                    Restablecer mi contraseña
                </a>
            </div>

            <p style="font-size: 14px; text-align: center; color: #6c757d; margin-top: 20px;">
                <em>Este enlace expirará en 60 minutos. Si no solicitaste este cambio, puedes ignorar este mensaje.</em>
            </p>
        </div>

        <!-- Pie de página -->
        <div
            style="background-color: #f1f3f5; padding: 20px; text-align: center; font-size: 13px; color: #6c757d; border-top: 1px solid #e9ecef;">
            <p style="margin: 0;">© {{ date('Y') }} RecetAPP. Todos los derechos reservados.</p>
        </div>

    </div>
</body>

</html>
