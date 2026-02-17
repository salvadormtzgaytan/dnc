{{-- resources/views/dnc/list.blade.php --}}

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
            <div class="text-center text-gray-600">
                <i class="fa-solid fa-circle-exclamation"></i>
                No tienes DNCs asignadas.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($dncs as $dnc)
                    <a href="{{ route('dncs.exams.list', $dnc) }}" class="block">
                        <x-card-dnc
                            :dnc="$dnc"
                            :image="$dnc->image_path ? asset('storage/' . $dnc->image_path) : null"
                            badge="{!! ($dnc->start_date && now()->lt($dnc->start_date)) 
                                        || ($dnc->end_date && now()->gt($dnc->end_date))
                                        ? '<span class=\'inline-block bg-red-500 text-white text-xs px-2 py-1 rounded-lg\'>Expirado</span>'
                                        : '' !!}"
                        >
                            {{-- Fechas --}}
                            <div class="grid grid-cols-1 gap-1 text-sm text-gray-600 mt-2">
                                <p><span class="font-medium">Fecha de inicio:</span>
                                   {{ $dnc->start_date?->format('d/m/Y') ?? '—' }}</p>
                                <p><span class="font-medium">Fecha de cierre:</span>
                                   {{ $dnc->end_date?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                        </x-card-dnc>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
    <div class="badge badge-warning badge-success badge-info badge-accent text-warning text-success text-info badge-neutral badge-error text-error text-neutral text-danger" style="display:none"></div>
</div>
@endsection
