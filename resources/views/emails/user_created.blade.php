@component('mail::message')
# ¡Bienvenido a {{ $appName }}!

Hola **{{ mb_strtoupper($user->name, 'UTF-8') }}**,
Tu cuenta ha sido creada exitosamente. A continuación tus credenciales de acceso:

@component('mail::panel')
**Correo:** {{ $user->email }}  
**Contraseña:** {{ $password }}
@endcomponent

@component('mail::button', ['url' => url('/login')])
Iniciar sesión
@endcomponent

Si tú no solicitaste esta cuenta, puedes ignorar este correo.

Gracias,<br>
El equipo de {{ $appName }}
@endcomponent
