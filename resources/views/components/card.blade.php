@props([
    'title'       => null,
    'image'       => null,
    'subtitle'    => null,
    'badge'       => null,
    'progress'    => null,
    'actions'     => [],
    'extraHeader' => null,
])

<div {{ $attributes->merge(['class' => 'border rounded-2xl shadow bg-white overflow-hidden']) }}>
    @if($image || $extraHeader)
        <div class="relative h-40 bg-gray-100">
            @if($image)
                <img src="{{ $image }}" alt="{{ $title }}" class="object-cover w-full h-full">
            @endif
            {!! $extraHeader !!}
        </div>
    @endif

    <div class="p-4 space-y-2">
        @if($title)
            <h3 class="font-semibold text-lg text-gray-800">{{ $title }}</h3>
        @endif

        @if($subtitle)
            <p class="text-sm text-gray-600">{{ $subtitle }}</p>
        @endif

        @if($badge)
            <div>{!! $badge !!}</div>
        @endif

        @if(! is_null($progress))
            <div>
                <progress class="w-full h-3 rounded" value="{{ $progress }}" max="100"></progress>
                <p class="text-right text-xs text-gray-500">{{ $progress }}% completado</p>
            </div>
        @endif

        @if(count($actions))
            <div class="flex gap-2 mt-2">
                @foreach($actions as $action)
                    {!! $action !!}
                @endforeach
            </div>
        @endif
    </div>
</div>
