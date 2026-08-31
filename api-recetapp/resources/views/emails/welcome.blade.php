<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bienvenido a RecetAPP</title>
</head>

<body
    style="background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 40px 20px; color: #212529;">

    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #e9ecef;">

        <!-- Cabecera (Color Primario) -->
        <div style="background-color: #0d6efd; padding: 25px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 1px;">
                🍳 RecetAPP
            </h1>
        </div>

        <!-- Contenido -->
        <div style="padding: 35px 30px;">
            <h2 style="margin-top: 0; color: #212529; font-size: 22px;">¡Hola, {{ $userName }}! 👋</h2>

            <p style="font-size: 16px; line-height: 1.6; color: #495057;">
                Gracias por registrarte en la plataforma. Tu cuenta ha sido activada y has creado la casa
                <strong>"{{ $houseName }}"</strong> correctamente.
            </p>

            <p style="font-size: 16px; line-height: 1.6; color: #495057;">
                Ya puedes empezar a añadir tus recetas favoritas, gestionar los ingredientes de tu nevera y planificar
                tus menús semanales de forma eficiente.
            </p>

            <!-- Botón de acción principal -->
            <div style="text-align: center; margin: 40px 0 20px;">
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4200') }}/login"
                    style="display: inline-block; padding: 14px 28px; background-color: #0d6efd; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">
                    Ir a mi cuenta
                </a>
            </div>
        </div>

        <!-- Pie de página -->
        <div
            style="background-color: #f1f3f5; padding: 20px; text-align: center; font-size: 13px; color: #6c757d; border-top: 1px solid #e9ecef;">
            <p style="margin: 0;">© {{ date('Y') }} RecetAPP. Todos los derechos reservados.</p>
        </div>

    </div>
</body>

</html>