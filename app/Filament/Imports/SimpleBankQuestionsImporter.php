<?php

namespace App\Filament\Imports;

use App\Models\Question;
use App\Models\CatalogSegment;
use App\Models\CatalogLevel;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SimpleBankQuestionsImporter extends Importer
{
    protected static ?string $model = Question::class;

    /**
     * Define las columnas disponibles para la importación, incluyendo relación con segment y level.
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('segment')
                ->relationship('segment', resolveUsing: fn (?string $state) =>
                    CatalogSegment::firstOrCreate([
                        'name' => trim($state ?? '') ?: 'Sin Segmento',
                    ])
                )
                ->label('SEGMENTO'),

            ImportColumn::make('level')
                ->relationship('level', resolveUsing: fn (?string $state) =>
                    CatalogLevel::firstOrCreate([
                        'name' => trim($state ?? '') ?: 'Sin Nivel',
                    ])
                )
                ->label('NIVEL'),

            ImportColumn::make('type')
                ->label('TIPO'),

            ImportColumn::make('explanation')
                ->label('EXPLICACIÓN'),

            ImportColumn::make('text')
                ->requiredMapping()
                ->rules(['required'])
                ->label('PREGUNTA'),
        ];
    }

    /**
     * Resuelve o crea el registro de la pregunta para cada fila.
     * Normaliza encabezados, previene duplicados y usa transacción.
     */
    public function resolveRecord(): ?Question
    {
        $bankId = $this->options['question_bank_id'] ?? null;
        if (! $bankId) {
            throw new RowImportFailedException('No se pudo determinar el banco de preguntas.');
        }

        // Normalizar encabezados (trim, eliminar BOM, espacios, a mayúsculas y sin espacios)
        $normalized = collect($this->data)
            ->mapWithKeys(function ($value, $key) {
                $key = preg_replace('/\x{FEFF}/u', '', $key);
                $key = trim($key);
                $key = str_replace(' ', '', $key);
                $key = Str::upper($key);
                return [$key => $value];
            });

        // Texto de pregunta y respuesta correcta
        $text = trim($normalized['TEXT'] ?? '');
        $correct = trim($normalized['RESPUESTACORRECTA'] ?? '');
        if (! $text || ! $correct) {
            throw new RowImportFailedException('Pregunta y respuesta correcta son obligatorias.');
        }

        // Evitar duplicados por texto y banco
        if ($existing = Question::where('question_bank_id', $bankId)->where('text', $text)->first()) {
            return $existing;
        }

        // Opciones de respuesta
        $options = $normalized
            ->filter(fn($value, $key) => preg_match('/^RESPUESTA\d+$/', $key) || $key === 'RESPUESTACORRECTA')
            ->filter(fn($v) => filled($v))
            ->map(fn($v, $key) => [
                'text'       => trim($v),
                'is_correct' => $key === 'RESPUESTACORRECTA',
            ])
            ->values();

        if ($options->count() < 2) {
            throw new RowImportFailedException('Debe haber al menos dos opciones de respuesta.');
        }
        if (! $options->contains(fn($opt) => $opt['is_correct'])) {
            throw new RowImportFailedException('No se marcó correctamente ninguna respuesta.');
        }

        // Tipo y explicación
        $rawType = strtolower(trim($normalized['TYPE'] ?? 'single'));
        $type = in_array($rawType, ['single', 'multiple']) ? $rawType : 'single';
        $expl = trim($normalized['EXPLICACION'] ?? '') ?: null;

        // Crear pregunta y opciones en transacción
        return DB::transaction(function () use ($bankId, $text, $type, $expl, $options) {
            $question = Question::create([
                'question_bank_id'   => $bankId,
                'title'              => Str::limit($text, 50),
                'text'               => $text,
                'type'               => $type,
                'default_score'      => 1,
                'shuffle_choices'    => true,
                'explanation'        => $expl,
            ]);

            // Crear opciones
            $question->choices()->createMany(
                $options->map(fn($opt, $i) => [
                    'text'       => $opt['text'],
                    'is_correct' => $opt['is_correct'],
                    'order'      => $i + 1,
                    'score'      => 1,
                ])->toArray()
            );

            return $question;
        });
    }

    /**
     * Mensaje al completar la importación.
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $msg = 'Importadas '.number_format($import->successful_rows).' preguntas.';
        if ($fails = $import->getFailedRowsCount()) {
            $msg .= ' Fallidas: '.$fails;
        }
        return $msg;
    }

    /**
     * Configuración CSV.
     */
    public static function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'delimiter'      => ',',
            'skip_bom'       => true,
        ];
    }
}
