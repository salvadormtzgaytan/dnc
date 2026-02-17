@extends('layouts.app')

@section('title', 'Perfil')

@section('body-bg', 'bg-perfil')

@section('content')
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Contenedor grid con distribución 50/50 desde lg (991px) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Columna izquierda (50%) -->
                <div>
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg h-full">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>

                <!-- Columna derecha (50%) con formularios apilados -->
                <div class="space-y-6">
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <!-- <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
@endsection