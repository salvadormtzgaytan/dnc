<x-mail::message>
# 📊 Resumen de la importación de usuarios

Se ha completado el proceso de importación:

- ✅ **Importados correctamente:** {{ $importedCount }}
- ❌ **Fallidos:** {{ $failedCount }}

@if($sampleUsers && $sampleUsers->isNotEmpty())
## 👥 Ejemplo de usuarios importados

@component('mail::table')
| Nombre | Correo | Puesto | Tienda | Fecha |
|--------|--------|--------|--------|-------|
@foreach ($sampleUsers as $user)
| {{ $user->name }} | {{ $user->email }} | {{ $user->profile->position->name ?? 'N/A' }} | {{ $user->profile->store->store_name ?? 'N/A' }} | {{ $user->created_at->format('d/m/Y H:i') }} |
@endforeach
@endcomponent
@endif

<x-mail::panel>
📎 Se ha adjuntado un archivo CSV con:

- Usuarios importados
- Contraseñas generadas (si aplica)
- Motivos de error para registros fallidos
</x-mail::panel>

Gracias por usar la plataforma,<br>
**{{ config('app.name') }}**
</x-mail::message>
