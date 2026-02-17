<x-filament-widgets::widget> 
    <x-filament::section>
        <x-slot name="headerEnd">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filter">
                    <option value="">Todas las concesionarias</option>
                    @foreach($this->getAvailableDealerships() as $dealershipName)
                        <option value="{{ $dealershipName }}">{{ $dealershipName }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        @forelse ($dncs as $dnc)
            <div class="mb-6">
                {{-- Nombre de la DNC --}}
                <div class="flex justify-between items-center mb-1">
                    <span class="font-semibold text-gray-800">{{ $dnc['name'] }}</span>
                    <span class="text-sm text-gray-500">{{ $dnc['progress'] }}%</span>
                </div>

                {{-- Barra de progreso --}}
                <div class="w-full bg-gray-200 rounded h-3 mb-2">
                    <div
                        class="bg-primary-600 h-3 rounded transition-all duration-300"
                        style="width: {{ $dnc['progress'] }}%;"
                    ></div>
                </div>

                {{-- Detalle de estados --}}
                <div class="text-sm text-gray-600 flex flex-wrap gap-4">
                    <div>✅ <span class="font-medium">{{ $dnc['completed'] }}</span> completados</div>
                    <div>🔄 <span class="font-medium">{{ $dnc['in_progress'] }}</span> en progreso</div>
                    <div>⏳ <span class="font-medium">{{ $dnc['not_started'] }}</span> sin iniciar</div>
                    <div>📊 <span class="font-medium">{{ $dnc['total'] }}</span> total</div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No hay DNC activas.</p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>

