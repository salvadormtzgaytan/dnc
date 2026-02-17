{{-- resources/views/dashboard.blade.php --}}

@php
use Illuminate\Support\Facades\Auth;
@endphp

@extends('layouts.app')

@section('title', 'Dashboard')
@section('body-bg', 'bg-dashboard')

@section('content')
<div class="flex items-center justify-center">
    <div class="max-w-7xl w-full px-4 py-8">
        {{-- Saludo --}}
        <h1 class="text-2xl font-semibold mb-6">
            ¡Hola, {{ mb_strtoupper(Auth::user()->name, 'UTF-8') }}!
        </h1>

        @if ($dncs->isEmpty())
            <div class="badge badge-error gap-2 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-4 w-4 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                No tienes DNCs asignadas.
            </div>
        @else
            {{-- Flex wrap centrado --}}
            <div class="flex flex-wrap justify-center gap-6">
                @foreach ($dncs as $dnc)
                    <div class="w-full sm:w-1/2 lg:w-1/3 xl:w-1/4">
                        <a href="{{ route('dncs.exams.list', $dnc) }}" class="block">
                            <x-card-dnc
                                :dnc="$dnc"
                                :image="$dnc->image_path ? asset('storage/' . $dnc->image_path) : null"
                                badge="{!! $dnc->is_expired
                                            ? '<span class=\'inline-block bg-red-500 text-white text-xs px-2 py-1 rounded-lg\'>Expirado</span>'
                                            : '' !!}"
                            />
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
