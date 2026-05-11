<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSettingResource\Pages;
use App\Models\UserSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserSettingResource extends Resource
{
    protected static ?string $model = UserSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Beállítások';
    protected static ?string $pluralModelLabel = 'Beállítások';
    protected static ?string $modelLabel = 'Beállítás';

    private const LEGAL_DOCUMENTS_CSS_SETTING = 'legal-documents-css';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Kulcs')
                    ->disabled()
                    ->dehydrated(false)
                    ->required(),

                Forms\Components\TextInput::make('value')
                    ->label('Érték')
                    ->required()
                    ->visible(fn (?UserSetting $record): bool => $record?->name !== self::LEGAL_DOCUMENTS_CSS_SETTING)
                    ->dehydrated(fn (?UserSetting $record): bool => $record?->name !== self::LEGAL_DOCUMENTS_CSS_SETTING),

                Forms\Components\Textarea::make('value')
                    ->label('CSS tartalom')
                    ->required()
                    ->rows(30)
                    ->columnSpanFull()
                    ->helperText('Ez a CSS a jogi dokumentum oldalakhoz kerül betöltésre.')
                    ->visible(fn (?UserSetting $record): bool => $record?->name === self::LEGAL_DOCUMENTS_CSS_SETTING)
                    ->dehydrated(fn (?UserSetting $record): bool => $record?->name === self::LEGAL_DOCUMENTS_CSS_SETTING),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kulcs')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Érték')
                    ->limit(80)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState()),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Utolsó módosítás')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserSettings::route('/'),
            'edit' => Pages\EditUserSetting::route('/{record}/edit'),
        ];
    }
}
