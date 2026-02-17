@extends('layouts.guest')

@section('body-bg', 'bg-forgot')

@section('content')
    <div class="w-full flex md:justify-end justify-center px-4 sm:px-0"> <!-- Contenedor principal alineado a la derecha -->
        <div class="w-full max-w-2xl px-6 py-8 bg-base-100 shadow-lg rounded-2xl">
            <div class="text-style-forgot-passw mb-4">
                ¿Olvidó su contraseña? No hay problema. Denos su correo y le enviaremos un enlace para restablecerla.
            </div>

            <!-- Estado de sesión -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Correo electrónico -->
                <div class="form-control mb-4">
                    <x-input-label for="email" :value="__('Correo electrónico')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <x-primary-button class="btn btn-accent">
                        {{ __('Enviar enlace de recuperación') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection