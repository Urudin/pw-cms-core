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
                            ->label('URL')
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
                                ->required(),
                            Forms\Components\TextInput::make('group_classes')
                                ->label('CSS Osztályok')
                                ->required(),
                            Forms\Components\TextInput::make('wrap_section_start')
                                ->label('Szakasz kezdete')
                                ->required(),
                            Forms\Components\TextInput::make('wrap_section_close')
                                ->label('Szakasz lezárása')
                                ->required(),

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
                            ->heading(fn ($record) => "Szekció: {$record->group_id}") // Group ID mindig látszódjon
                            ->collapsible() // Szekciók külön-külön összehajthatók
                            ->collapsed(), // Alapból ÖSSZECSUKVA!
                    ])
                    ->columnSpanFull()
                    ->orderColumn('order')
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

