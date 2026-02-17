<?php

namespace App\Filament\Resources\QuestionBankResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QuestionBankResource;
use App\Filament\Resources\QuestionBankResource\Actions\ImportQuestionsAction;

class ListQuestionBanks extends ListRecords
{
    protected static string $resource = QuestionBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
            Actions\CreateAction::make(),
        ];
    }
}
