<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invitación a RecetAPP</title>
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
            <h2 style="margin-top: 0; color: #198754; font-size: 22px;">¡Tienes una invitación! 💌</h2>

            <p style="font-size: 16px; line-height: 1.6; color: #495057;">
                <strong>{{ $inviterName }}</strong> te ha invitado a unirte a su casa
                <strong>"{{ $houseName }}"</strong> en la aplicación.
            </p>

            <div
                style="background-color: #f8f9fa; border-left: 4px solid #0d6efd; padding: 15px 20px; margin: 25px 0; border-radius: 4px;">
                <p
                    style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d; text-transform: uppercase; font-weight: 600;">
                    Tus credenciales temporales:</p>
                <p style="margin: 0; font-size: 18px; font-family: monospace; color: #212529;">
                    Usuario: <strong>{{ $email }}</strong><br>
                    Contraseña: <strong>{{ $tempPassword }}</strong>
                </p>
            </div>

            <p style="font-size: 16px; line-height: 1.6; color: #495057;">
                Para aceptar la invitación y vincular tu cuenta con esta casa, haz clic en el siguiente botón para
                verificar tu correo:
            </p>

            <!-- Botón de éxito (Verde) -->
            <div style="text-align: center; margin: 35px 0 10px;">
                <a href="{{ $activationUrl }}"
                    style="display: inline-block; padding: 14px 28px; background-color: #198754; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">
                    Activar mi cuenta
                </a>
            </div>

            <p style="font-size: 14px; text-align: center; color: #6c757d; margin-top: 20px;">
                <em>Una vez dentro, te recomendamos cambiar la contraseña y tu nombre desde el panel "Perfil".</em>
            </p>
        </div>

        <!-- Pie de página -->
        <div
            style="background-color: #f1f3f5; padding: 20px; text-align: center; font-size: 13px; color: #6c757d; border-top: 1px solid #e9ecef;">
            <p style="margin: 0;">© {{ date('Y') }} RecetAPP. Has recibido este correo porque alguien te ha invitado a
                usar la plataforma.</p>
        </div>

    </div>
</body>

</html>