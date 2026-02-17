@extends('layouts.guest')

@section('body-bg', 'bg-login')

@section('left-column')
    @include('components.login-card')
@endsection
@section('content')
    <div class="w-full max-w-md mx-auto px-4 py-5 md:px-6 md:py-8 bg-base-100 shadow-lg rounded-2xl">
        <!-- Estado de sesión -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Correo electrónico -->
            <div class="form-control mb-4">
                <label for="email" class="label">
                    <span class="label-text">Correo electrónico</span>
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input input-bordered input-primary w-full" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-error" />
            </div>

            <!-- Contraseña -->
            <div class="form-control mb-4">
                <label for="password" class="label">
                    <span class="label-text">Contraseña</span>
                </label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="input input-bordered input-primary w-full" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-error" />
            </div>

            <!-- Mantener sesión activa -->
            <div class="mb-6 flex justify-start">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" name="remember" class="checkbox" />
                    <span class="ms-2 text-gris-1">Mantener sesión activa</span>
                </label>
            </div>

            <!-- Botón e “Olvidó contraseña” -->
            <div class="flex flex-col items-center space-y-3 md:flex-row md:justify-end md:space-x-4 md:space-y-0">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-gris-1 hover:underline order-2 text-center md:order-0 mx-3">
                        ¿Olvidó su contraseña?
                    </a>
                @endif

                <button type="submit" class="btn btn-accent w-3/5 md:w-auto md:ml-auto uppercase">
                    Iniciar sesión
                </button>
            </div>
        </form>
    </div>
@endsection