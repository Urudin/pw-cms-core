<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockResource\Pages;
use App\Filament\Resources\BlockResource\RelationManagers;
use Filament\Forms\Components\View;
use App\Models\Block;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        // 📷 Egyedi gomb a kép beillesztésére
                        View::make('components.image-button')
                            ->columnSpan(1),

                        // ✏️ Tiptap szerkesztő maga
                        TiptapEditor::make('content')
                            ->extraAttributes(['id' => 'tiptap-editor'])
                            ->id('tiptap-editor')
                            ->profile('simple')
//                            ->tools(['bold', 'italic', 'strike', 'underline', 'link']), // Kép nincs, mert saját gombot használunk
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Név'),
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
            'index' => Pages\ListBlocks::route('/'),
            'create' => Pages\CreateBlock::route('/create'),
            'edit' => Pages\EditBlock::route('/{record}/edit'),
        ];
    }
}
