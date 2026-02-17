<x-mail::message>
# {{ $title ?? 'Notificación del sistema' }}

@if(!empty($message))
{{ $message }}
@endif

@if(!empty($details) && is_array($details))
<x-mail::panel>
@foreach($details as $label => $value)
- **{{ $label }}:** {{ $value }}
@endforeach
</x-mail::panel>
@endif

@if(!empty($footer))
{{ $footer }}
@endif

Gracias,<br>
**{{ config('app.name') }}**
</x-mail::message>
