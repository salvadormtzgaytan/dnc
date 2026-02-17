<?php

namespace App\Filament\Imports;

use App\Models\Question;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Str;

class BankQuestionsImporter extends Importer
{
    protected static ?string $model = Question::class;


    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Título'),

            ImportColumn::make('text')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Texto de la pregunta'),

            ImportColumn::make('type')
                ->requiredMapping()
                ->rules(['required', 'in:single,multiple'])
                ->label('Tipo (single/multiple)'),

            ImportColumn::make('default_score')
                ->label('Puntaje')
                ->numeric(),

            ImportColumn::make('shuffle_choices')
                ->label('Aleatorio (true/false)')
                ->boolean(),

            ImportColumn::make('segmento')
                ->label('Segmento') // <-- nuevo
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn($record, $state) => null),

            ImportColumn::make('nivel')
                ->label('Nivel') // <-- nuevo
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(fn ($record, $state) => null),
        ];
    }


    public function resolveRecord(): ?Question
    {
        $questionBankId = $this->options['question_bank_id'] ?? null;

        if (! $questionBankId) {
            throw new RowImportFailedException('No se pudo determinar el banco de preguntas.');
        }
        $choices = [];

        foreach ($this->data as $key => $value) {
            if (Str::startsWith($key, 'choice_') && Str::endsWith($key, '_text') && filled($value)) {
                $index = Str::between($key, 'choice_', '_text');
                $correctKey = "choice_{$index}_correct";

                $choices[] = [
                    'text' => $value,
                    'is_correct' => in_array(strtolower(trim($this->data[$correctKey] ?? '')), ['1', 'true'], true),
                    'order' => count($choices) + 1,
                ];
            }
        }

        if (count($choices) < 2) {
            throw new RowImportFailedException('Debe haber al menos 2 opciones.');
        }

        if (! collect($choices)->contains(fn($c) => $c['is_correct'] === true)) {
            throw new RowImportFailedException('Debe haber al menos una opción correcta.');
        }
        // Buscar y validar segmento
        $segmentName = $this->data['segmento'] ?? 'Sin Segmento';
        $segmentId = \App\Models\CatalogSegment::where('name', $segmentName)->value('id');

        if (! $segmentId) {
            throw new RowImportFailedException("El segmento '{$segmentName}' no existe en el catálogo.");
        }

        // Buscar y validar nivel
        $levelName = $this->data['nivel'] ?? 'Sin Nivel';
        $levelId = \App\Models\CatalogLevel::where('name', $levelName)->value('id');

        if (! $levelId) {
            throw new RowImportFailedException("El nivel '{$levelName}' no existe en el catálogo.");
        }

        $question = new Question([
            'question_bank_id' => $questionBankId, // ← Solo funciona en RelationManager
            'title' => $this->data['title'],
            'text' => $this->data['text'],
            'type' => $this->data['type'],
            'default_score' => $this->data['default_score'] ?? 1,
            'shuffle_choices' => in_array(strtolower(trim($this->data['shuffle_choices'] ?? '')), ['1', 'true'], true) ? 1 : 0,
            'catalog_segment_id' => $segmentId,
            'catalog_level_id' => $levelId,
        ]);

        $question->save();
        $question->choices()->createMany($choices);

        return $question;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación se completó: ' . number_format($import->successful_rows) . ' ' .
            str('fila')->plural($import->successful_rows) . ' importadas exitosamente.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' .
                str('fila')->plural($failedRowsCount) . ' no pudieron importarse.';
        }

        return $body;
    }

    public static function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'delimiter' => ',', // Asegúrate que esté en coma, no punto y coma
        ];
    }
}
