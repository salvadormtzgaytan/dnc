<?php

namespace App\Filament\Resources\DncUserOverrideResource\Pages;

use App\Filament\Resources\DncUserOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDncUserOverrides extends ListRecords
{
    protected static string $resource = DncUserOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
