@extends('layouts.app')

@section('title', 'Resultado del examen')

@section('body-bg', 'bg-finish') {{-- <- aquí sin punto y coma --}}

@section('content')
{{-- Overlay de carga, oculto por defecto --}}
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="flex flex-col items-center">
        {{-- Spinner simple con Tailwind --}}
        <div class="border-4 border-white border-opacity-75 border-t-transparent rounded-full w-12 h-12 animate-spin"></div>
        <p class="mt-4 text-white">Generando PDF, por favor espera…</p>
    </div>
</div>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-0 py-12 space-y-6 sm:space-y-10">
    {{-- Fila: a la izquierda datos del intento, a la derecha el puntaje --}}
    <div class="overflow-hidden rounded-2xl flex flex-col md:flex-row bg-blue-2 text-white shadow-xl" data-aos="flip-left">
        {{-- Izquierda: imagen al 40% en md+, full en móvil --}}
        <div class="w-full md:w-2/5 h-64 md:h-auto">
            @if ($exam->image_path && file_exists(public_path('storage/' . $exam->image_path)))
            <img src="{{ asset('storage/' . $exam->image_path) }}" alt="{{ $exam->name }}" class="object-cover w-full h-full" />
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
                <h2 class="text-2xl font-bold">{{ $exam->name }}</h2>
                {{-- Gestion de intentos --}}
                @if ($allCompletedAttempts->count() > 1)
                <p class="text-sm text-white">
                    Intentos:
                    @foreach ($allCompletedAttempts as $i => $item)
                    @if ($item->id === $attempt->id)
                    <span class="font-bold">#{{ $i + 1 }}</span>
                    @else
                    <a href="{{ route('exam.finish', ['exam' => $exam->id, 'attempt' => $item->id]) }}" class="link link-hover text-blue-500 hover:underline">#{{ $i + 1 }}</a>
                    @endif
                    @if (!$loop->last)
                    ,
                    @endif
                    @endforeach
                </p>
                @else
                <p class="text-sm text-white">Intento #{{ $attemptNumber }}</p>
                @endif
                {{-- --}}
                <p class="text-xs opacity-75">{{ $attempt->user->name }} &mdash; {{ $attempt->user->email }}</p>
            </div>

            {{-- Panel semitransparente --}}
            <div class="border border-white/50 rounded-lg p-4 flex flex-col md:flex-row items-start md:items-center justify-between mt-6 space-y-4 md:space-y-0">
                {{-- Calificación --}}
                <div class="text-center md:text-left md:flex-1">
                    <p class="text-sm uppercase">Calificación</p>
                    <div class="text-5xl font-extrabold my-2">{{ $percentage }}<i class="fa-duotone fa-solid fa-percent"></i></div>
                    <p class="text-xs"><i class="fa-duotone fa-solid fa-check-double"></i> ({{ $earnedScore }} /
                        {{ $maxScore }} pts)</p>
                </div>
                {{-- Detalles del intento --}}
                <div class="text-sm leading-tight text-left md:flex-1">
                    <p><i class="fa-duotone fa-solid fa-calendar-days"></i>
                        <strong>Inicio:</strong><br>{{ $started->format('d/m/Y H:i') }}
                    </p>
                    <p class="mt-2"><i class="fa-duotone fa-solid fa-calendar-days"></i>
                        <strong>Fin:</strong><br>{{ $finished->format('d/m/Y H:i') }}
                    </p>
                    <p class="mt-2"><i class="fa-duotone fa-solid fa-clock"></i>
                        <strong>Duración:</strong><br>{{ $durationFormatted }}
                    </p>
                </div>
            </div>
        </div>
    </div>


    {{-- Métricas generales --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-12">
        {{-- Tarjeta “Contestadas” --}}
        <div class="flex w-full max-w-xs overflow-hidden rounded-2xl shadow-lg" data-aos="fade-up">

            {{-- Panel izquierdo con fondo y icono --}}
            <div class="bg-yellow-500 w-16 flex items-center justify-center text-white">
                <i class="fa-duotone fa-solid fa-pen-to-square text-white text-2xl"></i>
            </div>
            {{-- Contenido derecho --}}
            <div class="bg-white flex-1 p-4">
                <p class="text-sm font-medium text-gray-800">Contestadas</p>
                <p class="text-2xl font-bold">{{ count($attempt->answers) }}</p>
            </div>

        </div>
        {{-- Correctas --}}
        <div class="flex w-full max-w-xs overflow-hidden rounded-2xl shadow-lg" data-aos="fade-up">
            <div class="bg-green-500 w-16 flex items-center justify-center text-white">
                <i class="fa-duotone fa-solid fa-circle-check text-2xl"></i>
            </div>
            <div class="bg-white flex-1 p-4">
                <p class="text-sm font-medium text-gray-700">Correctas</p>
                <p class="text-2xl font-bold">{{ $correctCount }}</p>
            </div>
        </div>

        {{-- Ranking --}}
        <div class="flex w-full max-w-xs overflow-hidden rounded-2xl shadow-lg" data-aos="fade-up">
            <div class="bg-blue-500 w-16 flex items-center justify-center text-white">
                <i class="fa-duotone fa-solid fa-layer-group text-2xl"></i>
            </div>
            <div class="bg-white flex-1 p-4">
                <p class="text-sm font-medium text-gray-700">
                    @if ($dealership)
                    Ranking dentro de {{ $dealership->name }}
                    @else
                    Ranking global
                    @endif
                </p>
                <p class="text-2xl font-bold mt-1">{{ $rankPosition }} <span class="text-base text-gray-500">de {{ $rankTotalUsers }}</span> </p>
            </div>
        </div>
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Gauge Chart: Nivel de dominio --}}
        <div class="p-6 rounded-2xl bg-white/40 backdrop-blur-md shadow-lg" data-aos="fade-up">
            <h2 class="card-title text-lg text-base-content flex items-center justify-between">
                <span>Nivel de dominio</span>
                <div class="badge {{ \App\Utils\ScoreColorHelper::daisyBadgeClass($percentage) }} text-white">
                    {{ \App\Utils\ScoreColorHelper::level($percentage) }}
                </div>
            </h2>


            <canvas data-speedometr-chart data-percentage="{{ $percentage }}" style="max-height: 300px;" class="w-full"></canvas>
            {{-- Porcentaje debajo --}}
            <div class="absolute top-[75%] left-[50%] -translate-x-1/2 text-4xl font-extrabold text-azulejo">
                {{ $percentage }}%
            </div>
        </div>

        {{-- Gráfico de line por segmento 
            <div class="p-6 rounded-2xl bg-white/40 backdrop-blur-md shadow-lg" data-aos="fade-up">
                <h2 class="card-title text-lg text-base-content text-center">Desempeño por segmento</h2>
                <canvas data-categories='@json($segmentData['labels'])' data-scores='@json($segmentData['scores'])'
                    data-chart-type="bar" data-chart-label="Tendencia por segmento" width="400" height="300"></canvas>
            </div>
            --}}
        {{-- Retroalimentación --}}
        @if (!empty($feedbackText))
        <div class="card bg-base-100 border border-blue-500 shadow-md rounded-2xl" data-aos="fade-up">
            <div class="card-body text-base-content">
                <h2 class="card-title text-lg text-primary"><i class="fa-duotone fa-solid fa-messages"></i>
                    Retroalimentación
                    personalizada</h2>
                <div class="mt-2 text-gray-700 space-y-2">
                    {!! $feedbackText !!}
                </div>
            </div>
        </div>
        @endif
    </div>



    {{-- Botón de descarga --}}
    <div class="text-center pt-6 space-y-4">

        {{-- Sólo muestro este botón si el usuario NO obtuvo 100 --}}
    @if($attempt->score < 100)
        <a href="{{ route('attempt.downloadWrong', ['attempt' => $attempt->id]) }}"
           class="btn btn-secondary"
           download
           target="_blank"
        >
            <i class="fa-duotone fa-file-excel"></i>
            Descargar mis áreas de mejora
        </a>
    @endif

        <form id="pdf-form" action="{{ route('attempt.downloadPdf', ['attempt' => $attempt->id]) }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="chart_image" id="chart_image_field" value="" />
        </form>

        <button id="btn-download-pdf" class="btn btn-primary">
            <i class="fa-duotone fa-file-pdf"></i> Descargar mi resultado (PDF)
        </button>

        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">
            <i class="fa-duotone fa-solid fa-house-heart"></i> Volver al inicio
        </a>
    </div>

</div>
@vite('resources/js/gauge-chart.js')
<script>
    const btn = document.getElementById('btn-download-pdf');
    const overlay = document.getElementById('loading-overlay');

    btn.addEventListener('click', async () => {
        // Evita clics dobles
        btn.disabled = true;
        // Muestra el overlay
        overlay.classList.remove('hidden');

        try {
            // 1) Captura el canvas en Base64
            const canvas = document.querySelector('[data-speedometr-chart]');
            const chartImage = canvas.toDataURL('image/png');

            // 2) Llama al endpoint por POST
            const response = await fetch("{{ route('attempt.downloadPdf', ['attempt' => $attempt->id]) }}", {
                method: 'POST'
                , headers: {
                    'Content-Type': 'application/json'
                    , 'X-CSRF-TOKEN': document.querySelector('#pdf-form input[name="_token"]').value
                , }
                , body: JSON.stringify({
                    chart_image: chartImage
                })
            , });

            if (!response.ok) {
                throw new Error('Error generando el PDF');
            }

            // 3) Descarga
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `resultado_{{ $attempt->id }}.pdf`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        } catch (err) {
            alert(err.message);
        } finally {
            // Siempre oculta el overlay y habilita el botón
            overlay.classList.add('hidden');
            btn.disabled = false;
        }
    });

</script>

@endsection
