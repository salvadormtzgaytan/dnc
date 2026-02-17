@php
    // Destacar preguntas sin responder si hay error de validación
    $showUnansweredHighlight = str_contains(session('error'), 'Debes responder todas las preguntas');

    // Calcular si todas las preguntas del examen han sido respondidas
    $totalQuestions = $exam->questions()->count();
    $answeredCount = count($attempt->answers ?? []);
    $allAnswered = $answeredCount >= $totalQuestions;
@endphp

@extends('layouts.app')
@section('body-bg', 'bg-exam')
@section('title', $exam->name)

@section('content')
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-12 sm:space-y-10 sm:px-6 lg:px-0">

        {{-- ======================
         Indicador de tiempo restante
         ====================== --}}
        @if ($timeLimit)
            <div class="mt-4 flex justify-center">
                <div class="badge badge-outline font-mono text-lg" id="countdown-display">
                    🕒 Cargando tiempo...
                </div>
            </div>
        @endif

        {{-- ======================
         Cabecera: título y paginado
         ====================== --}}
        <div class="text-center">
            <h1 class="text-3xl font-bold">{{ $exam->name }}</h1>
            <p class="badge badge-accent badge-outline mt-1 text-gray-500">
                Página {{ $questions->currentPage() }} de {{ $questions->lastPage() }}
            </p>
        </div>

        {{-- ======================
         Contenedor de toasts
         ====================== --}}
        <div id="toast-container" class="fixed right-6 top-6 z-50 flex w-full max-w-sm flex-col items-end space-y-2">
        </div>



        {{-- ======================
         Mensaje de error general
         ====================== --}}
        @if (session('error'))
            <div class="alert alert-error rounded-2xl text-white shadow-sm">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ======================
         Formulario de preguntas
         ====================== --}}
        <form method="POST" action="{{ route('exam.savePage', [$exam->id, 'page' => $questions->currentPage()]) }}">
            @csrf

            <div class="space-y-6">
                @foreach ($questions as $question)
                    @php
                        // ¿Esta pregunta está sin responder?
                        $unanswered = !isset($attempt->answers['question_' . $question->id]);
                    @endphp
                    <div data-aos="fade-right" data-question-id="{{ $question->id }}"
                        class="{{ $showUnansweredHighlight && $unanswered ? 'border-red-500 text-white' : 'border-base-200' }} card border bg-base-100 shadow-md">
                        <div class="card-body">
                            <h2 class="card-title text-azulejo">
                                {{ $loop->iteration + ($questions->currentPage() - 1) * $questions->perPage() }}.
                                {!! \Purifier::clean($question->text) !!}
                            </h2>

                            @php
                                // Selección previa del usuario
                                $selected = $attempt->answers['question_' . $question->id] ?? null;
                                $inputName = "question_{$question->id}" . ($question->type === 'multiple' ? '[]' : '');
                            @endphp

                            <div class="space-y-2">
                                @foreach ($question->choices as $choice)
                                    @php
                                        // ¿Está marcada esta opción?
                                        $isChecked = is_array($selected)
                                            ? in_array($choice->id, $selected)
                                            : $selected == $choice->id;
                                    @endphp

                                    <label for="choice_{{ $choice->id }}"
                                        class="{{ $isChecked ? 'border-blue-500 ring-1 ring-blue-300' : '' }} block cursor-pointer rounded-lg border border-gray-200 bg-white p-3 text-sm focus-within:ring focus-within:ring-blue-100 hover:border-blue-400">
                                        <div class="flex items-center">
                                            <input type="{{ $question->type === 'multiple' ? 'checkbox' : 'radio' }}"
                                                name="{{ $inputName }}" id="choice_{{ $choice->id }}"
                                                value="{{ $choice->id }}"
                                                class="mt-0.5 shrink-0 rounded-full border-gray-300 text-blue-600 focus:ring-blue-500"
                                                {{ $isChecked ? 'checked' : '' }}>
                                            <span class="ms-3 text-gray-700">{{ $choice->text }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sólo en la última página mostramos “Finalizar” --}}
            @if ($questions->currentPage() === $questions->lastPage())
                <div class="mt-8 text-center">
                    <button type="submit" name="action" value="finish" class="btn btn-secondary">
                        Finalizar examen
                    </button>
                </div>
            @endif


        </form>

        {{-- ======================
Paginación con DaisyUI v4 usando “join” y FontAwesome
====================== --}}

        @php
            $total = $questions->lastPage(); // para el “… N” si quieres
            $upperBound =
                $exam->navigation_method === 'sequential'
                    ? $allowedPage // límite real
                    : $total;
            $current = (int) $questions->currentPage();
            $window = 3;
            $half = floor($window / 2);

            $start = max(1, $current - $half);
            $end = min($upperBound, $current + $half);

            if ($end - $start + 1 < $window) {
                $start = max(1, $end - $window + 1);
                $end = min($upperBound, $start + $window - 1);
            }
        @endphp

        @if ($upperBound > 1)
            <div class="mt-8 flex justify-center">
                <div class="join">
                    {{-- ← Anterior --}}
                    @if ($questions->onFirstPage())
                        <button class="btn btn-disabled btn-primary join-item" id="prev-exam-button"><i
                                class="fa-duotone fa-chevron-left"></i>
                            Anterior</button>
                    @else
                        <a href="{{ route('exam.start', [$exam->id, 'page' => $current - 1]) }}"
                            class="btn btn-primary join-item" id="prev-exam-button"><i
                                class="fa-duotone fa-chevron-left"></i> Anterior</a>
                    @endif

                    @if (false)

                        {{-- “1 …” si hace falta --}}
                        @if ($start > 1)
                            <a href="{{ route('exam.start', [$exam->id, 'page' => 1]) }}"
                                class="btn btn-primary join-item">1</a>
                            <span class="btn btn-disabled join-item">…</span>
                        @endif

                        {{-- ventanas de páginas --}}
                        @for ($i = $start; $i <= $end; $i++)
                            @php $active=$i===$current; @endphp
                            <a href="{{ route('exam.start', [$exam->id, 'page' => $i]) }}"
                                class="{{ $active ? 'btn-active' : 'btn-primary' }} btn join-item">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- “… N” si hace falta --}}
                        @if ($end < $upperBound)
                            <span class="btn btn-disabled join-item">…</span>
                            <a href="{{ route('exam.start', [$exam->id, 'page' => $upperBound]) }}"
                                class="btn btn-primary join-item">{{ $upperBound }}</a>
                        @endif
                    @endif
                    {{-- Siguiente → --}}
                    @if ($questions->currentPage() === $questions->lastPage())
                        <button class="btn btn-disabled btn-primary join-item" id="next-exam-button">Siguiente <i
                                class="fa-duotone fa-chevron-right"></i></button>
                    @else
                        <a href="{{ route('exam.start', [$exam->id, 'page' => $current + 1]) }}"
                            class="btn btn-primary join-item" id="next-exam-button">Siguiente <i
                                class="fa-duotone fa-chevron-right"></i></a>
                    @endif
                </div>
            </div>
        @endif



        {{-- end Paginacion --}}

        {{-- ======================
Modal de tiempo agotado
====================== --}}
        <div class="badge badge-success badge-error" style="display:none;"></div>
        <input type="checkbox" id="timeout-modal" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box text-center">
                <h3 class="text-lg font-bold text-error">¡Tiempo agotado!</h3>
                <p class="py-4">Tu tiempo para este examen ha terminado.</p>
                <div class="modal-action justify-center">
                    <form method="POST" action="{{ route('exam.finish', $exam->id) }}">
                        @csrf
                        <button class="btn btn-primary">Cerrar examen</button>
                    </form>
                </div>
            </div>
        </div>
        @if (!empty($showTimeoutModal))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.getElementById('timeout-modal')?.showModal?.();
                });
            </script>
        @endif

        {{-- ======================
     Scripts de validación y guardado AJAX
     ====================== --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                let debounceTimer;
                const DEBOUNCE_TIME = 150; // Tiempo optimizado para respuesta rápida
                let hasFinished = false;

                // Función debounce mejorada
                const debounce = (fn, delay) => {
                    return (...args) => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => fn(...args), delay);
                    };
                };

                // Validación ultra optimizada
                const validateQuestionsAnswered = () => {
                    const questionIds = new Set();

                    document.querySelectorAll('[data-question-id]').forEach(container => {
                        questionIds.add(container.dataset.questionId);
                    });

                    return Array.from(questionIds).every(id => {
                        return document.querySelector(
                            `input[name="question_${id}"]:checked, 
             input[name="question_${id}[]"]:checked`
                        );
                    });
                };

                // Actualización de estado con debounce
                const updateFinishState = debounce(() => {
                    const allAnswered = validateQuestionsAnswered();
                    const isLastPage =
                        {{ $questions->currentPage() === $questions->lastPage() ? 'true' : 'false' }};
                    const nextButton = document.getElementById('next-exam-button');
                     const isSequential = "{{ $exam->navigation_method }}" === 'sequential';
                    if (!isSequential) {
                        if (nextButton) {
                            nextButton.disabled = isLastPage;
                            nextButton.classList.toggle('btn-disabled', isLastPage);
                            nextButton.classList.toggle('opacity-75', isLastPage);
                        }
                    } else {
                        if (isLastPage) {
                            if (nextButton) {
                                nextButton.disabled = isLastPage;
                                nextButton.classList.toggle('btn-disabled', isLastPage);
                                nextButton.classList.toggle('opacity-75', isLastPage);
                            }
                        } else {
                            if (nextButton) {
                                nextButton.disabled = !allAnswered;
                                nextButton.classList.toggle('btn-disabled', !allAnswered);
                                nextButton.classList.toggle('opacity-75', !allAnswered);
                            }
                        }
                    }

                }, DEBOUNCE_TIME);

                // Toast mejorado con animación
                const showToast = (message, type = 'error') => {
                    const container = document.getElementById('toast-container');
                    const toast = document.createElement('div');

                    toast.className = `badge badge-${type === 'success' ? 'success' : 'error'} text-white 
                         flex items-center gap-2 p-3 rounded-lg shadow-lg animate-fade-in`;
                    toast.innerHTML = `
            <i class="fa-duotone ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
            <span>${message}</span>
        `;

                    container.appendChild(toast);
                    setTimeout(() => {
                        toast.classList.add('animate-fade-out');
                        setTimeout(() => toast.remove(), 300);
                    }, 1000);
                };

                // Delegación de eventos centralizada
                document.addEventListener('change', async (e) => {
                    if (!e.target.matches('input[type=radio], input[type=checkbox]')) return;

                    updateFinishState();

                    try {
                        const id = e.target.name.match(/\d+/)[0];
                        const isMultiple = e.target.name.includes('[]');
                        const value = isMultiple ? [...document.querySelectorAll(
                                `input[name="question_${id}[]"]:checked`)]
                            .map(el => el.value) :
                            e.target.value;

                        const response = await fetch(`{{ route('exam.saveAnswer', $exam->id) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                question_id: id,
                                value
                            })
                        });

                        const data = await response.json();
                        showToast(data.message || 'Respuesta guardada', data.success ? 'success' : 'error');
                    } catch (error) {
                        showToast('Error de conexión', 'error');
                        console.error('Error:', error);
                    }
                });

                // Temporizador optimizado
                const timeLimitVal = @json($timeLimit);
                if (timeLimitVal > 0) {
                    let remaining = timeLimitVal;
                    const display = document.getElementById('countdown-display');
                    const finishUrl = @json(route('exam.finishAjax', $exam->id));

                    const timer = setInterval(() => {
                        const hours = Math.floor(remaining / 3600).toString().padStart(2, '0');
                        const minutes = Math.floor((remaining % 3600) / 60).toString().padStart(2, '0');
                        const seconds = (remaining % 60).toString().padStart(2, '0');

                        if (display) {
                            display.textContent = `🕒 ${hours}:${minutes}:${seconds}`;
                            display.classList.toggle('text-error', remaining < 60);
                        }

                        if (remaining <= 0 && !hasFinished) {
                            clearInterval(timer);
                            hasFinished = true;

                            fetch(finishUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            }).then(() => {
                                document.getElementById('timeout-modal').checked = true;
                            }).catch(console.error);
                        }

                        remaining--;
                    }, 1000);
                }

                // Validación inicial doble para asegurar
                updateFinishState();
                setTimeout(updateFinishState, 200);
            });
        </script>
    @endsection
