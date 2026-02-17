<x-filament-panels::page>
    @if($this->attemptId && !$this->attempt)
        <div class="p-4 bg-danger-100 text-danger-800 rounded">
            Error: No se encontró el intento con ID {{ $this->attemptId }}
        </div>
    @else
        {{ $this->table }}
        
        @if($this->getTableRecords()->isEmpty())
            <div class="p-4 bg-warning-100 text-warning-800 rounded mt-4">
                @if($this->attemptId)
                    No se encontraron respuestas para este intento.
                @else
                    No se encontraron respuestas. Prueba a filtrar por un intento específico.
                @endif
            </div>
        @endif
    @endif
</x-filament-panels::page>


