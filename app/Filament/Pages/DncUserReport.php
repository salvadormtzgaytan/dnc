<?php

namespace App\Filament\Pages;

use App\Models\CatalogStore;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Utils\ScoreColorHelper;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\Builder;

/**
 * Clase DncUserReport
 *
 * Esta página de Filament muestra un reporte de usuarios DNC, permitiendo visualizar:
 * - Información básica del usuario (nombre, correo, rol, tienda, puesto, número de empleado).
 * - Número de intentos de examen realizados.
 * - Mejor puntuación obtenida y nivel de dominio.
 * - Fecha del último intento.
 *
 * Además, permite exportar el reporte a Excel, ver los intentos de cada usuario y navegar entre tiendas y usuarios.
 */
class DncUserReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.dnc-user-report';
    protected static ?string $title = 'Reporte de Usuarios';
    protected static ?string $navigationLabel = 'Reporte DNC - Usuarios';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $slug = 'dnc-user-report';
    protected static ?int $navigationSort = 6;

    #[\Livewire\Attributes\Url]
    public ?int $storeId = null;

    /**
     * Cambia el título de la página si se filtra por tienda.
     *
     * @return void
     */
    public function mount(): void
    {
        if ($this->storeId) {
            static::$title = 'Reporte de Usuarios - ' . CatalogStore::find($this->storeId)?->name;
        }
    }

    /**
     * Define la tabla principal del reporte de usuarios.
     *
     * - Muestra columnas con información relevante del usuario y sus exámenes.
     * - Permite exportar los datos a Excel.
     * - Permite navegar entre tiendas y usuarios, y ver los intentos de cada usuario.
     *
     * @param Tables\Table $table
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(function () {
                $query = User::query()
                    ->when($this->storeId, function ($query) {
                        $query->whereHas('profile', function (Builder $query) {
                            $query->where('store_id', $this->storeId);
                        });
                    })
                    ->with([
                        'profile.store',
                        'roles',
                        'examAttempts' => fn($query) => $query
                            ->where('status', 'completed')
                            ->orderByDesc('score')
                    ]);

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, User $record) {
                        if ($record->roles->isEmpty()) {
                            return 'Sin rol';
                        }

                        $roleNames = $record->roles->pluck('name')->map(function ($role) {
                            return match ($role) {
                                'super_admin' => 'Super Administrador',
                                'admin' => 'Administrador',
                                'dealership' => 'Dealership',
                                'manager' => 'Manager',
                                'participante' => 'Participante',
                                default => ucfirst($role)
                            };
                        });

                        return $roleNames->join(', ');
                    })
                    ->color(function ($state, User $record) {
                        if ($record->roles->isEmpty()) {
                            return 'gray';
                        }

                        $mainRole = $record->roles->first()->name;
                        return match ($mainRole) {
                            'super_admin' => 'danger',
                            'admin' => 'secondary',
                            'dealership' => 'warning',
                            'manager' => 'info',
                            'participante' => 'success',
                            default => 'primary'
                        };
                    }),

                Tables\Columns\TextColumn::make('profile.store.name')
                    ->label('Tienda')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sin tienda asignada')
                    ->visible(fn() => !$this->storeId),

                Tables\Columns\TextColumn::make('profile.position.name')
                    ->label('Puesto')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sin puesto definido'),

                Tables\Columns\TextColumn::make('profile.employee_number')
                    ->label('Núm. Empleado')
                    ->placeholder('Sin número'),

                Tables\Columns\TextColumn::make('exam_attempts_count')
                    ->label('Intentos')
                    ->counts('examAttempts')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('best_score')
                    ->label('Mejor Puntuación')
                    ->getStateUsing(function (User $record) {
                        $bestAttempt = $record->examAttempts->first();
                        return $bestAttempt ? $bestAttempt->score : null;
                    })
                    ->formatStateUsing(fn($state) => $state
                        ? "{$state}% (" . ScoreColorHelper::level((float) $state) . ")"
                        : 'N/A')
                    ->badge()
                    ->color(fn($state) => ScoreColorHelper::forScore((float) $state)),

                Tables\Columns\TextColumn::make('last_attempt_date')
                    ->label('Último Intento')
                    ->getStateUsing(fn(User $record) => $record->examAttempts->first()?->created_at)
                    ->dateTime(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar a Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn() => 'reporte-usuarios-' . now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'profile.store',
                                'roles',
                                'examAttempts'
                            ]))
                            ->withColumns([
                                \pxlrbt\FilamentExcel\Columns\Column::make('best_score')
                                    ->heading('Mejor Puntuación (%)')
                                    ->getStateUsing(function (User $record) {
                                        $bestAttempt = $record->examAttempts->first();
                                        return $bestAttempt ? $bestAttempt->score : null;
                                    }),

                                \pxlrbt\FilamentExcel\Columns\Column::make('last_attempt_date')
                                    ->heading('Fecha Último Intento')
                                    ->getStateUsing(fn(User $record) => $record->examAttempts->first()?->created_at),
                            ])
                    ]),
                /**
                 * Acción para ver todos los usuarios si se está filtrando por tienda.
                 */
                Action::make('verTodos')
                    ->label('Ver todos los usuarios')
                    ->url(route('filament.admin.pages.dnc-user-report'))
                    ->icon('heroicon-o-list-bullet')
                    ->color('secondary')
                    ->visible(fn() => $this->storeId),
                /**
                 * Acción para volver al listado de tiendas si se está filtrando por tienda.
                 */
                Action::make('back_to_stores')
                    ->label('Volver a Tiendas')
                    ->url(route('filament.admin.pages.dnc-store-reports'))
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->visible(fn() => $this->storeId),
            ])
            ->actions([
                /**
                 * Acción para ver los intentos de examen de un usuario.
                 */
                Action::make('verIntentos')
                    ->label('Ver intentos')
                    ->url(fn(User $record): string => route('filament.admin.pages.dnc-user-attempts', ['userId' => $record->id]))
                    ->icon('heroicon-o-document-text')
                    ->button()
                    ->color('primary'),
            ]);
    }
}