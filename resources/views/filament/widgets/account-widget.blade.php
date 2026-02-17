{{-- resources/views/filament/widgets/account-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-2 gap-4">
            <x-filament::card heading="Usuarios Totales">
                <div class="text-3xl font-bold">
                    {{ $this->getTotalUsers() }}
                </div>
            </x-filament::card>

            <x-filament::card heading="Perfiles Activos">
                <div class="text-3xl font-bold">
                    {{ $this->getActiveProfiles() }}
                </div>
            </x-filament::card>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

