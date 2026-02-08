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
    protected static ?string $navigationLabel = 'Oldalak';
    protected static ?string $pluralModelLabel = 'Oldalak';
    protected static ?string $modelLabel = 'Oldal';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                // Alapadatok
                Forms\Components\Fieldset::make('Alapadatok')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Név')
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),

                // SEO Beállítások
                Forms\Components\Fieldset::make('SEO Beállítások')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta title'),
                        Forms\Components\Textarea::make('meta_keywords')
                            ->label('Meta keywords'),
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta description'),
                    ])
                    ->columns(2),

                // Szekciók kezelése különálló kártyákban
                Repeater::make('pageBlocks')
                    ->relationship()
                    ->label('Szekciók')
                    ->schema([
                        Forms\Components\Card::make() // Minden egyes szekció egy külön kártya
                        ->schema([
                            Forms\Components\TextInput::make('group_id')
                                ->label('Csoport ID')
                                ->nullable(),
                            Forms\Components\TextInput::make('group_classes')
                                ->label('CSS Osztályok')
                                ->nullable(),
                            Forms\Components\TextInput::make('wrap_section_start')
                                ->label('Szakasz kezdete')
                                ->nullable(),
                            Forms\Components\TextInput::make('wrap_section_close')
                                ->label('Szakasz lezárása')
                                ->nullable(),

                            // Blokkok külön kártyában, de a szekción belül
                            Forms\Components\Card::make()
                                ->schema([
                                    Repeater::make('blocks')
                                        ->relationship('blocks')
                                        ->schema([
                                            Select::make('block_id')
                                                ->relationship('block', 'name')
                                                ->label('Blokk')
                                                ->required(),
                                        ])
                                        ->orderColumn('order')
                                        ->reorderable(),
                                ])
                                ->label('Blokkok')
                                ->heading('Blokkok')
                                ->collapsible()
                                ->collapsed(), // Blokkok is alapból csukva
                        ])
                            ->heading(fn ($record) => "Szekció: {$record?->group_id}") // Group ID mindig látszódjon
                            ->collapsible() // Szekciók külön-külön összehajthatók
                            ->collapsed(), // Alapból ÖSSZECSUKVA!
                    ])
                    ->columnSpanFull()
                    ->deletable()
                    ->orderColumn('order')
                    ->reorderable(),
                // Szekciók kezelése különálló kártyákban
                Repeater::make('pageTiles')
                    ->relationship()
                    ->label('Widgetek')
                    ->schema([
                        Select::make('tile_id')
                            ->relationship('tile', 'title')
                            ->label('Widget')
                            ->required(),
                    ])
                    ->columnSpanFull()
                    ->deletable()
                    ->orderColumn('order')
                    ->reorderable()
                    ->collapsible()
                    ->collapsed()
            ]);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Név'),
                Tables\Columns\TextColumn::make('title')->label('Title'),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
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

