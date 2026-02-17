@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'email-error-text-style']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
