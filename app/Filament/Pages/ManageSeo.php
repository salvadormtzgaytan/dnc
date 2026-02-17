<?php

namespace App\Filament\Pages;

use App\Settings\SeoSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageSeo extends SettingsPage
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $settings = SeoSettings::class;
    protected static ?string $navigationLabel = 'SEO del sitio';
    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $title = 'Configuración SEO';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('pages')
                    ->label('Configuraciones por sección')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clave de sección')
                            ->placeholder('Ej. start, contact, exams')
                            ->required()
                            ->helperText('Identificador interno en inglés para la sección del sitio.'),
                        Forms\Components\TextInput::make('title')->label('Título SEO'),
                        Forms\Components\Textarea::make('description')->label('Descripción'),
                        Forms\Components\TextInput::make('keywords')->label('Palabras clave'),
                        Forms\Components\TextInput::make('og_title')->label('Título Open Graph'),
                        Forms\Components\Textarea::make('og_description')->label('Descripción Open Graph'),
                        Forms\Components\TextInput::make('twitter_card')->label('Tipo Twitter Card')->default('summary_large_image'),
                        Forms\Components\FileUpload::make('image')
                            ->label('Imagen para compartir')
                            ->image()
                            ->disk('public')
                            ->directory('seo'),
                    ])
                    ->columnSpanFull()
                    ->reorderable()
                    ->cloneable()
                    ->itemLabel(fn(array $state): ?string => $state['key'] ?? null),

                Forms\Components\Section::make('Configuración global del sitio')
                    ->description('Valores generales que aplican en todo el sitio')
                    ->schema([
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('ID de Google Analytics')
                            ->placeholder('Ej. G-XXXXXXXXXX'),
                        Forms\Components\TextInput::make('google_site_verification')
                            ->label('Código de verificación del sitio')
                            ->placeholder('Ej. código para meta tag de Google'),
                    ])
                    ->columns(2)
            ]);
    }
}
