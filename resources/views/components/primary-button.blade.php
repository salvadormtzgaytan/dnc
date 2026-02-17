<button {{ $attributes->merge(['type' => 'submit', 'class' => 'button-primary btn btn-primary']) }}>
    {{ $slot }}
</button>
