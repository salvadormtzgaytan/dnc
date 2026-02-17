<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Filtrar por Período
                </label>
                <select 
                    wire:model.live="selectedPeriod"
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Todos los períodos</option>
                    @foreach($this->getPeriods() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
