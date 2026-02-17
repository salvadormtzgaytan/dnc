<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="margin: 0; padding: 0; background-color: #dceef4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" style="background: white; border-radius: 6px; padding: 40px; text-align: center;">
                    {{-- Logo --}}
                    <tr>
                        <td style="padding-bottom: 20px;">
                            <img src="{{ asset('images/comex-logo.png') }}" alt="Comex Centro de Formación Integral" height="60">
                        </td>
                    </tr>

                    {{-- Emoji/Icon --}}
                    <tr>
                        <td style="padding: 10px 0;">
                            <img src="{{ asset('images/smile-icon.png') }}" alt="Emoji" width="40">
                        </td>
                    </tr>

                    {{-- Título --}}
                    <tr>
                        <td style="font-size: 22px; color: #0b7ec4; font-weight: bold; padding: 10px 0;">
                            ¡Hola! {{ $user->name ?? 'usuario' }}
                        </td>
                    </tr>

                    {{-- Texto principal --}}
                    <tr>
                        <td style="color: #333; font-size: 16px; padding: 10px 20px;">
                            Ha recibido este mensaje porque se solicitó un restablecimiento de contraseña para su cuenta.
                        </td>
                    </tr>

                    {{-- Botón --}}
                    <tr>
                        <td style="padding: 20px;">
                            <a href="{{ $url }}"
                               style="background-color: #f2911b; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: bold; display: inline-block;">
                                Restablecer contraseña
                            </a>
                        </td>
                    </tr>

                    {{-- Expiración --}}
                    <tr>
                        <td style="color: #333; font-size: 14px; padding-bottom: 10px;">
                            Este enlace de restablecimiento de contraseña expirará en 60 minutos.
                        </td>
                    </tr>

                    {{-- Advertencia --}}
                    <tr>
                        <td style="color: #333; font-size: 14px; padding-bottom: 10px;">
                            Si no ha solicitado el restablecimiento de contraseña, omita este mensaje de correo electrónico.
                        </td>
                    </tr>

                    {{-- Firma --}}
                    <tr>
                        <td style="color: #333; font-size: 14px; padding-bottom: 20px;">
                            Gracias,<br>
                            <strong>DNC Centro de Formación Integral</strong>
                        </td>
                    </tr>

                    {{-- Enlace alternativo --}}
                    <tr>
                        <td style="border-top: 1px solid #ccc; padding-top: 20px; color: #555; font-size: 13px; word-break: break-all;">
                            Si está teniendo problemas al hacer clic en el botón “Restablecer contraseña”, copie y pegue la URL de abajo en su navegador web:<br>
                            <a href="{{ $url }}" style="color: #0b7ec4;">{{ $url }}</a>
                        </td>
                    </tr>
                </table>

                {{-- Footer --}}
                <p style="font-size: 12px; color: #999; padding-top: 20px;">
                    © {{ date('Y') }} DNC Centro de Formación Integral. Todos los derechos reservados.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
