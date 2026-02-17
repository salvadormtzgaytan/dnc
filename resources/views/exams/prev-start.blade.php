@extends('layouts.app')

@section('title', 'Presentación DNC')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-0 py-12 space-y-6 sm:space-y-10">
        {{-- Fila: a la izquierda datos del intento, a la derecha el puntaje --}}
        <div class="overflow-hidden rounded-2xl flex flex-col md:flex-row bg-blue-2 text-white shadow-xl"
            data-aos="flip-left">
            {{-- Izquierda: imagen al 40% en md+, full en móvil --}}
            <div class="w-full md:w-2/5 h-64 md:h-auto">
                @if ($exam->image_path && file_exists(public_path('storage/' . $exam->image_path)))
                    <img src="{{ asset('storage/' . $exam->image_path) }}" alt="{{ $exam->name }}"
                        class="object-cover w-full h-full" />
                @else
                    <div class="w-full h-full bg-gray-300 flex items-center justify-center text-gray-500">
                        Sin imagen
                    </div>
                @endif
            </div>

            {{-- Derecha: contenido al 60% en md+, full en móvil --}}
            <div class="w-full md:w-3/5 p-6 md:p-8 flex flex-col justify-between">
                {{-- Título y usuario --}}
                <div class="space-y-2 text-center md:text-left">
                    <h2 class="text-2xl font-bold">Bienvenido(a) a la DNC {{ $exam->name }}</h2>
                    <p>
                        A continuación, se presenta una serie de preguntas.
                    </p>
                    <p>
                        Lee cuidadosamente y asegúrate de responder todas antes de finalizar tu examen.
                    </p>
                    <a href="{{ route('exam.start', $exam->id) }}" class="btn btn-secondary">
                        <i class="fa-duotone fa-regular fa-rocket-launch"></i> Comenzar
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection