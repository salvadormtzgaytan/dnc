@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:border-impulso dark:focus:border-impulso focus:ring-impulso dark:focus:ring-impulso rounded-md shadow-sm']) !!}>
