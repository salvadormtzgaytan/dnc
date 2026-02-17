@php
  use Illuminate\Support\Facades\Auth;
  use App\Utils\ScoreColorHelper;
@endphp

@extends('layouts.app')

@section('title', 'Dashboard')
@section('body-bg', 'bg-dashboard')

@section('content')
  <div class="flex items-center justify-center" id="main-view">
    <div class="w-full max-w-7xl px-4 py-8">

      {{-- Mensaje de error desde el backend --}}
      @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-500 p-4 text-white">
          {{ session('error') }}
        </div>
      @endif

      <div class="mb-6">
        <!-- Botón (arriba en móvil, derecha en desktop) -->
        <div class="mb-3 flex justify-end sm:hidden">
          <a class="btn btn-primary" href="{{ route('dashboard') }}">
            <i class="fa-duotone fa-solid fa-house-heart"></i> Inicio
          </a>
        </div>

        <!-- Desktop: Título izquierda, botón derecha -->
        <div class="hidden items-center justify-between sm:flex">
          <h1 class="text-2xl font-semibold">
            ¡Hola, {{ mb_strtoupper(Auth::user()->name, 'UTF-8') }}!
          </h1>
          <a class="btn btn-primary" href="{{ route('dashboard') }}">
            <i class="fa-duotone fa-solid fa-house-heart"></i> Inicio
          </a>
        </div>

        <!-- Título (abajo en móvil, alineado derecha) -->
        <div class="text-left sm:hidden">
          <h1 class="text-2xl font-semibold">
            ¡Hola, {{ mb_strtoupper(Auth::user()->name, 'UTF-8') }}!
          </h1>
        </div>
      </div>

      {{-- Single DNC check --}}

      @if ($dnc)
        @if ($dnc->is_expired)
          <p class="bg-white p-3 text-center text-gray-500">
            <i class="fa-solid fa-circle-exclamation"></i>
            Las fecha de disponibilidad de está DNC ya a vencido.
          </p>
        @else
          <div>
            <div class="mb-5 flex items-center gap-2">
              <h2 class="border-l-[10px] border-blue-1 pl-2 text-blue-1">
                {{ $dnc->name }}
              </h2>
            </div>

            {{-- Fechas de disponibilidad --}}
            @php
              $override = $dnc->userOverrides->where('user_id', Auth::id())->first();
            @endphp

            @if ($dnc->start_date || $dnc->end_date || $override)
              <div class="mb-4 rounded-lg {{ $override ? 'bg-green-50' : 'bg-blue-50' }} p-3">
                <div class="flex flex-wrap gap-4 text-sm {{ $override ? 'text-green-800' : 'text-blue-800' }}">
                  @if ($override)
                    <div class="w-full mb-2 flex items-center gap-2 font-semibold">
                      <i class="fa-solid fa-shield-check"></i>
                      <span>Acceso extendido</span>
                    </div>
                  @endif

                  @if ($override && $override->custom_start_date)
                    <div class="flex items-center gap-1">
                      <i class="fa-solid fa-calendar-days"></i>
                      <span class="font-medium">Inicio:</span>
                      <span>{{ $override->custom_start_date->format('d/m/Y h:i A') }}</span>
                    </div>
                  @elseif ($dnc->start_date)
                    <div class="flex items-center gap-1">
                      <i class="fa-solid fa-calendar-days"></i>
                      <span class="font-medium">Inicio:</span>
                      <span>{{ $dnc->start_date->format('d/m/Y h:i A') }}</span>
                    </div>
                  @endif

                  @if ($override && $override->custom_end_date)
                    <div class="flex items-center gap-1">
                      <i class="fa-solid fa-calendar-xmark"></i>
                      <span class="font-medium">Fin:</span>
                      <span>{{ $override->custom_end_date->format('d/m/Y h:i A') }}</span>
                    </div>
                  @elseif ($dnc->end_date)
                    <div class="flex items-center gap-1">
                      <i class="fa-solid fa-calendar-xmark"></i>
                      <span class="font-medium">Fin:</span>
                      <span>{{ $dnc->end_date->format('d/m/Y h:i A') }}</span>
                    </div>
                  @endif
                </div>
              </div>
            @endif

            {{-- Métricas de la DNC --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
              <div class="flex overflow-hidden rounded-2xl bg-white shadow">
                <div class="flex w-16 items-center justify-center bg-blue-500 text-white">
                  <i class="fa-duotone fa-chart-simple text-2xl"></i>
                </div>
                <div class="flex-1 p-4">
                  <p class="text-sm font-medium text-gray-700">Promedio general</p>
                  <p class="text-2xl font-bold">
                    {{ $dnc->average_score !== null ? $dnc->average_score . '%' : '—' }}
                  </p>
                </div>
              </div>

              <div class="flex overflow-hidden rounded-2xl bg-white shadow">
                <div class="flex w-16 items-center justify-center bg-green-500 text-white">
                  <i class="fa-duotone fa-gauge-high text-2xl"></i>
                </div>
                <div class="flex-1 p-4">
                  <p class="text-sm font-medium text-gray-700">Nivel de dominio</p>
                  <p class="text-2xl font-bold">
                    {{ ScoreColorHelper::level($dnc->average_score) }}
                  </p>
                </div>
              </div>

              <div class="flex overflow-hidden rounded-2xl bg-white shadow">
                <div class="flex w-16 items-center justify-center bg-yellow-500 text-white">
                  <i class="fa-duotone fa-star-half-stroke text-2xl"></i>
                </div>
                <div class="flex-1 p-4">
                  <p class="text-sm font-medium text-gray-700">Mejor porcentaje</p>
                  <p class="text-base text-gray-500">{{ $dnc->best_exam ?? '—' }}</p>
                  <p class="mt-1 text-2xl font-bold">
                    {{ $dnc->best_score !== null ? $dnc->best_score . '%' : '—' }}
                  </p>
                </div>
              </div>
            </div>

            {{-- Exámenes dentro del DNC --}}
            @if ($dnc->exams->isNotEmpty())
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($dnc->exams as $exam)
                  @if ($exam->enabled)
                    @php
                      $completedAttempt = $exam->attempts
                          ->where('status', 'completed')
                          ->sortByDesc('finished_at')
                          ->first();
                      $maxScore = $exam->attempts->where('status', 'completed')->max('score');
                      $inProgressAttempt = $exam->attempts->firstWhere('status', 'in_progress');
                    @endphp

                    <div class="card rounded-2xl border border-gray-200 bg-base-100 shadow-xl" data-aos="fade-up">
                      {{-- Imagen --}}
                      <figure class="relative m-0 overflow-hidden rounded-2xl px-3 pt-3">
                        @if ($exam->image_path && file_exists(public_path('storage/' . $exam->image_path)))
                          <img alt="{{ $exam->name }}" class="h-full w-full rounded-2xl object-cover object-top"
                            src="{{ asset('storage/' . $exam->image_path) }}" />
                        @else
                          <div class="absolute inset-0 flex items-center justify-center bg-gray-200 text-gray-500">
                            Sin imagen
                          </div>
                        @endif
                      </figure>

                      <div class="card-body space-y-3 p-4">
                        <h3 class="card-title text-azulejo">{{ $exam->name }}</h3>
                        @php $rounded = round($maxScore, 1); @endphp
                        @if ($maxScore !== null)
                          <div class="{{ ScoreColorHelper::daisyBadgeClass($rounded) }} badge text-sm font-semibold">
                            Calificación: {{ $rounded }}%
                          </div>
                        @endif

                        <p class="text-sm text-gris-1">
                          Inicio:
                          {{ $exam->start_at ? \Carbon\Carbon::parse($exam->start_at)->format('d/m/Y') : 'Disponible' }}
                        </p>

                        <div class="relative w-full">
                          <p class="mt-1 text-right text-xs text-gray-500">
                            {{ ($exam->progress ?? 0) > 0 ? $exam->progress . '% completado' : 'No iniciado' }}
                          </p>
                          @if ($exam->progress === null)
                            <div class="shimmer-overlay absolute left-0 top-0 h-3 w-full rounded">
                            </div>
                          @endif
                          <progress
                            class="{{ $exam->progress === 100 ? 'progress-success' : 'progress-primary' }} progress h-3 w-full"
                            max="100" value="{{ $exam->progress ?? 0 }}"></progress>
                        </div>

                        <div class="card-actions mt-2">
                          @php
                            $attemptsDone = $exam->attempts->where('status', 'completed')->count();
                            $maxAttempts = $exam->max_attempts ?? 1;
                          @endphp

                          @if ($inProgressAttempt)
                            <a class="btn btn-primary" href="{{ route('exam.preview', $exam->id) }}">
                              Continuar <i class="fa-solid fa-arrow-right ml-2"></i>
                            </a>
                          @elseif ($attemptsDone < $maxAttempts)
                            <a class="btn btn-primary" href="{{ route('exam.preview', $exam->id) }}">
                              {{ $attemptsDone > 0 ? 'Iniciar nuevo intento' : 'Comenzar' }}
                              <i class="fa-solid fa-arrow-right ml-2"></i>
                            </a>
                          @endif

                          @if ($completedAttempt)
                            <a class="btn btn-secondary" href="{{ route('exam.finish', $exam->id) }}">
                              Ver resultado <i class="fa-solid fa-square-poll-vertical ml-2"></i>
                            </a>
                          @endif
                        </div>
                      </div>
                    </div>
                  @endif
                @endforeach
              </div>
            @else
              <p class="bg-white p-3 text-center text-gray-500">
                <i class="fa-solid fa-circle-exclamation"></i>
                No hay exámenes disponibles en esta DNC.
              </p>
            @endif
          </div>
          {{-- Gráfica de la DNC --}}
          @php
            $scores = $dnc->chart_scores ?? [];
            $numericScores = collect($scores)->filter(fn($v) => is_numeric($v));
            $hasData = $numericScores->count() > 0 && $numericScores->sum() > 0;
          @endphp
          @if ($hasData)
            <div class="mt-8" data-aos="fade-up">
              <div class="card w-full bg-base-100 shadow-xl">
                <div class="card-body">
                  <h3 class="card-title text-azulejo">Resultados por examen</h3>
                  <canvas class="w-full" data-categories='@json($dnc->chart_labels)' data-chart-label=""
                    data-chart-type="bar" data-scores='@json($dnc->chart_scores)'>
                  </canvas>
                </div>
              </div>
            </div>
          @endif
        @endif
      @else
        <div class="text-center text-gray-600">
          <p><i class="fa-solid fa-circle-exclamation"></i> No tienes DNC asignada.</p>
        </div>
      @endif
    </div>
  </div>
  @vite('resources/js/category-chart.js')
@endsection
