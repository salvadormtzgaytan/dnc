<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\UserGroup;
use Filament\Tables\Table;
use App\Utils\UploadLimits;
use App\Utils\PasswordGenerator;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserResource extends Resource
{
    protected static ?string $model            = User::class;
    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationLabel  = 'Usuarios';
    protected static ?int $navigationSort      = 1;
    protected static ?string $modelLabel       = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup  = 'Gestión de Usuarios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->suffixIcon('heroicon-m-user')
                ->required()
                ->autocomplete(false)
                ->maxLength(255)
                ->columnSpanFull()
                ->mutateDehydratedStateUsing(fn (?string $state) => strtoupper($state)),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->suffixIcon('heroicon-m-at-symbol')
                ->required()
                ->autocomplete(false)
                ->maxLength(255)
                ->unique('users', 'email', ignoreRecord: true)
                ->columnSpanFull()
                ->live(onBlur: true)
                ->mutateDehydratedStateUsing(fn (?string $state) => strtolower($state)),
            TextInput::make('rfc')
    ->label('RFC')
    ->maxLength(15)
    
    ->dehydrateStateUsing(fn ($state) => mb_strtoupper($state)), // Asegura mayúsculas
            Select::make('roles')
                ->label('Rol')
                ->preload()
                ->nullable()
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'label',
                    modifyQueryUsing: fn (Builder $query) => auth()->user()?->hasRole('super_admin')
                        ? $query
                        : $query->where('name', '!=', 'super_admin')
                )
                ->columnSpanFull()
                ->helperText(
                    fn () => Role::count() === 0
                        ? new HtmlString('🟠 <span class="font-semibold text-yellow-700">No hay roles registrados.</span>')
                        : null
                ),

            Toggle::make('generate_password')
                ->label('Generar contraseña automáticamente')
                ->reactive()
                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                ->afterStateUpdated(
                    function ($state, callable $set) {
                        if ($state) {
                            $set('password', PasswordGenerator::generate());
                            $set('send_email', true);
                        } else {
                            $set('password', null);
                            $set('send_email', false);
                        }
                    }
                )
                ->default(false),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),

            Toggle::make('send_email')
                ->label('Enviar correo de bienvenida')
                ->reactive()
                ->requiredIfAccepted('generate_password')
                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                ->default(false),

            Toggle::make('password_update')
                ->label('Actualizar contraseña')
                ->reactive()
                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord)
                ->dehydrated(false)
                ->default(false),

            TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->maxLength(65)
                ->revealable()
                ->required(
                    fn ($get, $livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord
                        || $get('password_update')
                )
                ->visible(
                    fn ($get, $livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord
                        || $get('password_update')
                )
                ->dehydrated(fn ($get) => filled($get('password')))
                ->autocomplete('new-password')
                ->live(onBlur: true)
                ->columnSpanFull(),

            TextInput::make('password_confirmation')
                ->label('Confirmar Contraseña')
                ->password()
                ->maxLength(255)
                ->revealable()
                ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                ->same('password')
                ->dehydrated(false)
                ->visible(
                    fn ($get, $livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord
                        && ! $get('generate_password')
                )
                ->columnSpanFull(),

            Select::make('groups')
                ->label('Grupos de Usuarios')
                ->relationship('groups', 'name')
                ->multiple()
                ->preload()
                ->helperText(
                    fn () => UserGroup::count() === 0
                        ? new HtmlString('🟠 <span class="font-semibold text-yellow-700">No hay grupos de usuarios registrados.</span>')
                        : null
                ),

            FileUpload::make('profile_photo')
                ->label('Foto de Perfil')
                ->image()
                ->directory('users/profile_photos')
                ->visibility('public')
                ->imageEditor()
                ->imageCropAspectRatio('1:1')
                ->imageResizeMode('cover')
                ->imageResizeTargetWidth(300)
                ->imageResizeTargetHeight(300)
                ->maxSize(UploadLimits::maxSize('image'))
                ->acceptedFileTypes(UploadLimits::mimeTypes('image'))
                ->previewable(true)
                ->columnSpanFull(),

            Section::make('Perfil')
                ->relationship('profile')
                ->columns(2)
                ->schema([
                    Select::make('store_id')
                        ->relationship('store', 'name')
                        ->label('Tienda')
                        ->searchable(['name', 'external_store_id'])
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->external_store_id} - {$record->name}")
                        ->nullable()
                        ->preload()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $store = \App\Models\CatalogStore::find($state);
                                $set('dealership_id', $store?->dealership_id);
                                $set('zone_id', $store?->zone_id);
                            } else {
                                $set('dealership_id', null);
                                $set('zone_id', null);
                            }
                        })
                        ->live(),
                    
                    Hidden::make('dealership_id'),
                    Hidden::make('zone_id'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('profile.store.external_store_id')
                    ->label('ID TDA')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('name')->label('Nombre')->sortable()->searchable(),
                ImageColumn::make('profile_photo')->label('Foto')->circular(),
                TextColumn::make('email')->label('Correo Electrónico')->sortable()->searchable(),
                TextColumn::make('roles.name')->label('Rol')->badge(),
                ToggleColumn::make('is_active')->label('Activo')->sortable(),
                TextColumn::make('created_at')->label('Fecha de creación')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Fecha de actualización')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Filtrar por Rol')
                    ->options(
                        fn () => Role::query()
                            ->when(! auth()->user()->hasRole('super_admin'), fn ($q) => $q->where('name', '!=', 'super_admin'))
                            ->pluck('name', 'name')
                    ),
                SelectFilter::make('is_active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
                SelectFilter::make('store_id')
                    ->label('Filtrar por Tienda')
                    ->relationship('profile.store', 'name'),
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()->can('restore_user') || auth()->user()->can('force_delete_user')),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => auth()->user()->can('update_user')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()->can('delete_user')),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->can('force_delete_user')),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->can('restore_user')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Habilitar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Usuarios habilitados')
                            ->body('Los usuarios seleccionados han sido habilitados.')
                    ),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deshabilitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Usuarios deshabilitados')
                            ->body('Los usuarios seleccionados han sido deshabilitados.')
                    ),
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete_user')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('force_delete_user')),
                Tables\Actions\RestoreBulkAction::make()
                    ->visible(fn () => auth()->user()->can('restore_user')),
                ExportBulkAction::make(),
            ])
            ->striped()
            ->reorderable();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'profile',
                'roles',
                'groups',
                'profile.store',
            ])
            ->when(
                auth()->user()->can('force_delete_user') || auth()->user()->can('restore_user'),
                fn ($query) => $query->withoutGlobalScopes([SoftDeletingScope::class])
            );
    }
}
