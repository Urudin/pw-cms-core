<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?string $navigationLabel = 'Videók';
    protected static ?string $modelLabel = 'Videó';
    protected static ?string $pluralModelLabel = 'Videók';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Videó adatok')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Cím')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Leírás')
                        ->rows(6)
                        ->maxLength(10000)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('video_url')
                        ->label('Videó link')
                        ->required()
                        ->url()
                        ->maxLength(2048)
                        ->helperText('Pl. Vimeo/YouTube vagy privát link.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('thumbnail_url')
                        ->label('Thumbnail URL')
                        ->url()->maxLength(2048)
                        ->columnSpanFull(),


                    Forms\Components\TextInput::make('price_huf')
                        ->label('Ár (HUF)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->suffix('Ft'),

                    self::getCategoryField('video_type_id', 'Videó típusa', 'type'),
                    self::getCategoryField('video_domain_id', 'Videó szakterülete', 'domain'),
                    self::getCategoryField('video_topic_id', 'Videó témája', 'topic'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktív')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Cím')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('price_huf')
                    ->label('Ár')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' Ft'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktív')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('thumbnail_url')->label('Thumbnail URL')->columnSpanFull(),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('Létrehozva')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktív'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }

    private static function getCategoryField(string $fieldName, string $label, string $relationShipName): Forms\Components\Select
    {
        return Forms\Components\Select::make($fieldName)
            ->label($label)
            ->relationship(
                name: $relationShipName,
                titleAttribute: 'name',
                modifyQueryUsing: fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            )
            ->searchable()
            ->preload()
            ->createOptionAction(fn ($action) => $action->label('Új hozzáadása'))
            ->createOptionForm([
                Forms\Components\TextInput::make('name')
                    ->label('Név')
                    ->required()
                    ->maxLength(120),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktív')
                    ->default(true),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sorrend')
                    ->numeric()
                    ->default(0),
            ]);
    }

}
