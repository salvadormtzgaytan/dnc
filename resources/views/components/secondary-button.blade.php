<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn uppercase bg-gray-900 text-white']) }}>
    {{ $slot }}
</button>
