<?php

namespace App\Filament\Resources\DncResource\Pages;

use App\Filament\Resources\DncResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDnc extends CreateRecord
{
    protected static string $resource = DncResource::class;

    protected function afterCreate(): void
    {
        // Al crear siempre se crea un nuevo período
        $this->record->createNewPeriod();
    }
}
