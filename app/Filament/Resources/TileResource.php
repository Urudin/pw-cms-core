<?php

namespace App\Filament\Resources;

use AbdelhamidErrahmouni\FilamentMonacoEditor\MonacoEditor;
use App\Filament\Resources\TileResource\Pages;
use App\Filament\Resources\TileResource\RelationManagers;
use App\Models\Tile;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TileResource extends Resource
{
    protected static ?string $model = Tile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('Cím')->required(),
                        // 📷 Egyedi gomb a kép beillesztésére
                        View::make('components.image-button')
                            ->columnSpan(1),
                        MonacoEditor::make('content')
                            ->language('html')
                            ->required()
                            ->previewHeadEndContent("<script src='https://cdn.tailwindcss.com'></script><script defer src='https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js'></script>"),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Cím'),
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
            'index' => Pages\ListTiles::route('/'),
            'create' => Pages\CreateTile::route('/create'),
            'edit' => Pages\EditTile::route('/{record}/edit'),
        ];
    }
}
