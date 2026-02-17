<?php

namespace App\Filament\Pages;

use App\Models\CatalogStore;
use App\Models\ExamAttempt;
use App\Utils\ScoreColorHelper;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class DncStoreReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static string $view = 'filament.pages.dnc-store-report';
    protected static ?string $title = 'Reporte por Tienda';
    protected static ?string $navigationLabel = 'Reporte DNC - Tiendas';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $slug = 'dnc-store-reports';
    protected static ?int $navigationSort = 5;

    #[\Livewire\Attributes\Url(as: 'dealership')]
    public ?int $dealership = null;

    public function mount(): void
    {
        if ($this->dealership) {
            static::$title = 'Reporte de Tiendas - Concesionario ' . $this->dealership;
        }
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn () => CatalogStore::with([
                    'profiles.user.examAttempts',
                    'dealership',
                    'state',
                    'city',
                ])
                ->when($this->dealership, fn ($query) => $query->where('dealership_id', $this->dealership))
                ->withRealExamAverage()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Tienda')->searchable(),
                Tables\Columns\TextColumn::make('dealership.name')->label('Concesionario')->visible(fn () => !$this->dealership)->badge(),
                Tables\Columns\TextColumn::make('participants_count')->label('Participantes')
                    ->getStateUsing(fn ($record) =>
                        $record->profiles
                            ->filter(fn ($p) => $p->user !== null)
                            ->count()
                    )
                    ->badge(),
                Tables\Columns\TextColumn::make('real_exam_avg')->label('Promedio Examen')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state
                        ? number_format($state, 1) . ' % (' . ScoreColorHelper::level((float) $state) . ')'
                        : 'N/A')
                    ->color(fn ($state) => ScoreColorHelper::forScore((float) $state))
                    ->getStateUsing(fn ($record) => $record->real_exam_avg),
                Tables\Columns\TextColumn::make('exam_attempts_count')->label('Exámenes Completados')
                    ->getStateUsing(function ($record) {
                        $userIds = $record->profiles
                            ->filter(fn ($p) => $p->user !== null)
                            ->pluck('user.id');

                        return ExamAttempt::whereIn('user_id', $userIds)
                            ->where('status', 'completed')
                            ->count();
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('external_store_id')->label('ID TDA')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('external_account_number')->label('Cuenta')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('business_name')->label('Razón Social')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('state.name')->label('Estado'),
                Tables\Columns\TextColumn::make('city.name')->label('Ciudad'),
                Tables\Columns\TextColumn::make('address')->label('Dirección')->wrap(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar a Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('reporte-tiendas-' . now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'profiles.user.examAttempts',
                                'dealership',
                                'state',
                                'city',
                            ])->withRealExamAverage()),
                    ]),
                Action::make('verTodas')
                    ->label('Ver todas las tiendas')
                    ->url(route('filament.admin.pages.dnc-store-reports'))
                    ->icon('heroicon-o-list-bullet')
                    ->color('secondary')
                    ->visible(fn () => $this->dealership),
            ])
            ->actions([
                Action::make('verDetalle')
                    ->label('Ver detalle')
                    ->url(fn (CatalogStore $record): string => route('filament.admin.pages.dnc-user-report', ['storeId' => $record->id]))
                    ->icon('heroicon-o-arrow-right')
                    ->button()
                    ->color('primary'),
            ])->defaultSort('real_exam_avg', 'desc');
    }
}
