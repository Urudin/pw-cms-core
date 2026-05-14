<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalContentResource\Pages;
use App\Models\LegalContent;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kahusoftware\FilamentCkeditorField\CKEditor;

class LegalContentResource extends Resource
{
    protected static ?string $model = LegalContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Beállítások';
    protected static ?string $navigationLabel = 'Jogi Dokumentumok';
    protected static ?string $pluralModelLabel = 'Jogi Dokumentumok';
    protected static ?string $modelLabel = 'Jogi Dokumentum';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->required()
                    ->maxLength(255)
                    ->rules(['regex:/^[a-z0-9-]+$/'])
                    ->unique(ignoreRecord: true),
                CKEditor::make('content')
                    ->required()
                    ->columnSpanFull()
                    ->uploadUrl(null)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('url')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalContents::route('/'),
            'create' => Pages\CreateLegalContent::route('/create'),
            'edit' => Pages\EditLegalContent::route('/{record}/edit'),
        ];
    }
}
