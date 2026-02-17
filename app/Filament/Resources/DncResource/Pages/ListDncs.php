<?php

namespace App\Filament\Resources\DncResource\Pages;

use App\Filament\Resources\DncResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDncs extends ListRecords
{
    protected static string $resource = DncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
