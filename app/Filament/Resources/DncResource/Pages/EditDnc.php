<?php

namespace App\Filament\Resources\DncResource\Pages;

use App\Filament\Resources\DncResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDnc extends EditRecord
{
    protected static string $resource = DncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pasar el flag al request para que el Observer lo use
        // Si update_current_period es FALSE, significa que quiere crear nuevo período
        $updateCurrent = $data['update_current_period'] ?? true;
        
        \Log::info('EditDnc mutateFormDataBeforeSave', [
            'update_current_period' => $updateCurrent,
            'create_new_period' => !$updateCurrent,
        ]);
        
        request()->merge(['create_new_period' => !$updateCurrent]);
        
        return $data;
    }
}
