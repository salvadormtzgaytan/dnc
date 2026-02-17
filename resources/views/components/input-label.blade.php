@props(['value'])

<label {{ $attributes->merge(['class' => 'email-text-style']) }}>
    {{ $value ?? $slot }}
</label>
