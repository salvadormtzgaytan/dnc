{{-- resources/views/components/card-dnc.blade.php --}}
@props(['dnc', 'image' => null, 'badge' => null])

@php
    use App\Utils\ScoreColorHelper;
    use Illuminate\Support\Facades\Auth;

    $title = $dnc->name;
    $averageRaw = $dnc->average_score;
    $average = $averageRaw !== null ? number_format($averageRaw, 2) . '%' : '—';
    $averageClass = ScoreColorHelper::daisyBadgeClass($averageRaw);

    $domainLevel = ScoreColorHelper::level($averageRaw);
    $domainClass = ScoreColorHelper::forScore($averageRaw);

    $bestRaw = $dnc->best_score;
    $bestScore = $bestRaw !== null ? number_format($bestRaw, 2) . '%' : '—';
    $bestClass = ScoreColorHelper::daisyBadgeClass($bestRaw);

    // Verificar si hay anulación para este usuario
    $override = $dnc->userOverrides->where('user_id', Auth::id())->first();
    
    $startDate = $override && $override->custom_start_date 
        ? $override->custom_start_date->format('d/m/Y h:i A')
        : ($dnc->start_date ? $dnc->start_date->format('d/m/Y h:i A') : '—');
        
    $endDate = $override && $override->custom_end_date
        ? $override->custom_end_date->format('d/m/Y h:i A')
        : ($dnc->end_date ? $dnc->end_date->format('d/m/Y h:i A') : '—');
@endphp

<div {{ $attributes->merge(['class' => 'border rounded-2xl shadow bg-white overflow-hidden']) }}>
    {{-- Imagen de cabecera --}}
    @if ($image)
        <div class="relative h-32 bg-gray-100">
            <img src="{{ $image }}" alt="{{ $title }}" class="object-cover w-full h-full">
        </div>
    @endif

    <div class="p-4 space-y-4">
        {{-- Título y badge en columna --}}
        <div class="flex flex-col items-start gap-2">
            <h3 class="font-semibold text-lg text-primary">{{ $title }}</h3>
            @if ($override)
                <span class="badge badge-success text-white text-xs">
                    <i class="fa-solid fa-shield-check mr-1"></i> Acceso extendido
                </span>
            @endif
            @if ($badge)
                <div>{!! $badge !!}</div>
            @endif
        </div>

        {{-- Fechas de la DNC --}}
        <div class="grid grid-cols-1 gap-2 text-sm text-gray-600">
            <div>
                <p class="font-medium">Fecha de inicio:<br><span class="font-normal">{{ $startDate }}</span></p>
            </div>
            <div>
                <p class="font-medium">Fecha de cierre:<br><span class="font-normal">{{ $endDate }}</span></p>
            </div>
        </div>

        {{-- Métricas con badges de color --}}
        <div class="grid grid-cols-2 text-center gap-2">
            <div>
                <p class="text-xs text-gray-500">Promedio</p>
                <span class="badge {{ $averageClass }} mt-1">{{ $average }}</span>
            </div>
            <div>
                <p class="text-xs text-gray-500">Dominio</p>
                <span class="font-bold mt-1 text-{{ $domainClass }}">{{ $domainLevel }}</span>
            </div>
        </div>

        {{-- Slot para contenido extra (botones, enlaces, etc.) --}}
        {{ $slot }}
    </div>
</div>
