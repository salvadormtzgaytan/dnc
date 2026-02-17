{{-- resources/views/components/card-exam.blade.php --}}
@props(['exam'])

@php
    use App\Utils\ScoreColorHelper;

    // Intento completado más reciente
    $completedAttempt = $exam->attempts
        ->where('status', 'completed')
        ->sortByDesc('finished_at')
        ->first();

    // Puntuación máxima
    $maxScore = $exam->attempts
        ->where('status', 'completed')
        ->max('score');

    // Intento en curso
    $inProgressAttempt = $exam->attempts
        ->firstWhere('status', 'in_progress');

    // Progreso (0–100 o null)
    if ($inProgressAttempt && is_array($inProgressAttempt->question_order) && is_array($inProgressAttempt->answers)) {
        $totalQuestions = count($inProgressAttempt->question_order);
        $answered = count(array_filter(
            $inProgressAttempt->answers,
            fn($value, $key) => str_starts_with($key, 'question_') && $value !== null,
            ARRAY_FILTER_USE_BOTH
        ));
        $progress = $totalQuestions > 0
            ? round(($answered / $totalQuestions) * 100)
            : 0;
    } elseif ($exam->attempts->where('status','completed')->isNotEmpty()) {
        $progress = 100;
    } else {
        $progress = null;
    }

    // Etiqueta “No iniciado” o “X% completado”
    $progressLabel = $progress === null
        ? 'No iniciado'
        : ($progress === 100
            ? '100% completado'
            : $progress . '% completado'
        );

    // Formato de fecha de inicio
    $startLabel = $exam->start_at
        ? \Carbon\Carbon::parse($exam->start_at)->format('d/m/Y')
        : 'Disponible';

    // Clases de badge
    $scoreBadgeClass = $maxScore !== null
        ? ScoreColorHelper::daisyBadgeClass(round($maxScore, 1))
        : '';
@endphp

<div class="card bg-base-100 shadow-xl rounded-2xl border border-gray-200" data-aos="fade-up">
    {{-- Imagen --}}
    <figure class="overflow-hidden rounded-t-2xl m-0 relative px-3 pt-3 h-48">
        @if ($exam->image_path && file_exists(public_path('storage/' . $exam->image_path)))
            <img src="{{ asset('storage/' . $exam->image_path) }}"
                 alt="{{ $exam->name }}"
                 class="object-cover object-top w-full h-full rounded-2xl" />
        @else
            <div class="absolute inset-0 bg-gray-200 flex items-center justify-center text-gray-500">
                Sin imagen
            </div>
        @endif
    </figure>

    <div class="card-body p-4 space-y-3">
        {{-- Título --}}
        <h3 class="card-title text-azulejo">{{ $exam->name }}</h3>

        {{-- Badge de calificación --}}
        @if ($maxScore !== null)
            <div class="text-sm font-semibold badge {{ $scoreBadgeClass }}">
                Calificación: {{ round($maxScore, 1) }}%
            </div>
        @endif

        {{-- Fecha de inicio --}}
        <p class="text-sm text-gris-1">
            Inicio: {{ $startLabel }}
        </p>

        {{-- Barra de progreso --}}
        <div class="w-full relative">
            <p class="text-right text-xs text-gray-500 mt-1">{{ $progressLabel }}</p>
            @if ($progress === null)
                <div class="absolute top-0 left-0 w-full h-3 shimmer-overlay rounded"></div>
            @endif
            <progress
                class="progress h-3 w-full {{ $progress === 100 ? 'progress-success' : 'progress-primary' }}"
                value="{{ $progress ?? 0 }}"
                max="100"
            ></progress>
        </div>

        {{-- Acciones --}}
        <div class="card-actions mt-2">
            @php
                $attemptsDone = $exam->attempts->where('status', 'completed')->count();
                $maxAttempts  = $exam->max_attempts ?? 1;
            @endphp

            @if ($inProgressAttempt)
                <a href="{{ route('exam.preview', $exam->id) }}" class="btn btn-primary">
                    Continuar <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            @elseif ($attemptsDone < $maxAttempts)
                <a href="{{ route('exam.preview', $exam->id) }}" class="btn btn-primary">
                    {{ $attemptsDone > 0 ? 'Iniciar nuevo intento' : 'Comenzar' }}
                    <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            @endif

            @if ($completedAttempt)
                <a href="{{ route('exam.finish', $exam->id) }}" class="btn btn-secondary">
                    Ver resultado <i class="fa-solid fa-square-poll-vertical ml-2"></i>
                </a>
            @endif
        </div>
    </div>
</div>
