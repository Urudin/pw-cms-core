<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Block;
use App\Models\Page;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use FilamentTiptapEditor\TiptapEditor;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Név')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required(),
                Forms\Components\TextInput::make('slug')
                    ->label('URL')
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('meta_title')
                    ->label('Meta title'),
                Forms\Components\Textarea::make('meta_keywords')
                    ->label('Meta keywords'),
                Forms\Components\Textarea::make('meta_description')
                    ->label('Meta description'),
                Repeater::make('pageBlocks')
                    ->relationship()
                    ->schema([
                        Select::make('block_id')
                            ->relationship('block', 'name')
                    ])
                    ->columnSpanFull()
                    ->orderColumn('order')
                    ->reorderableWithButtons()
                    ->reorderable(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Név'),
                Tables\Columns\TextColumn::make('title')->label('Title'),
                Tables\Columns\TextColumn::make('slug')->label('URL'),
                Tables\Columns\TextColumn::make('meta_title')->label('Meta title'),
            ])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}

