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
use Illuminate\Support\Facades\Storage;

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

                    Forms\Components\FileUpload::make('thumbnail_url')
                        ->label('Thumbnail kép')
                        ->image()
                        ->directory('thumbnails')
                        ->disk('public') // vagy 's3'
                        ->visibility('public')
                        ->helperText('Ajánlott méret: 1280×720 px (16:9), JPG/PNG.')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeTargetWidth('1280')
                        ->imageResizeTargetHeight('720')
                        ->getUploadedFileNameForStorageUsing(fn ($file) => uniqid() . '.' . $file->getClientOriginalExtension())
                        ->saveUploadedFileUsing(function ($file, $record) {
                            $path = $file->store('thumbnails', 'public');

                            // URL-t mentünk az adatbázisba
                            return Storage::disk('public')->url($path);
                        })
                        // 🔑 Ez a lényeg: csak akkor mentse, ha van új feltöltés
                        ->dehydrated(fn ($state) => filled($state))
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('duration_seconds')
                        ->label('Videó hossza')
                        ->helperText('Formátum: mm:ss vagy hh:mm:ss (pl. 12:34 vagy 1:02:03)')
                        ->placeholder('mm:ss vagy hh:mm:ss')
                        ->dehydrateStateUsing(function (?string $state): ?int {
                            if ($state === null || trim($state) === '') {
                                return null;
                            }

                            $state = trim($state);

                            // támogatott: m:ss / mm:ss / h:mm:ss / hh:mm:ss
                            if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $state)) {
                                return null; // a validation úgyis megfogja
                            }

                            $parts = array_map('intval', explode(':', $state));

                            return count($parts) === 2
                                ? ($parts[0] * 60 + $parts[1])                 // mm:ss
                                : ($parts[0] * 3600 + $parts[1] * 60 + $parts[2]); // hh:mm:ss
                        })
                        ->formatStateUsing(function ($state): ?string {
                            if ($state === null) {
                                return null;
                            }

                            $total = (int) $state;
                            $h = intdiv($total, 3600);
                            $m = intdiv($total % 3600, 60);
                            $s = $total % 60;

                            return $h > 0
                                ? sprintf('%d:%02d:%02d', $h, $m, $s)
                                : sprintf('%d:%02d', $m, $s);
                        })
//                        ->rules([
//                            // Validáljuk a formátumot
//                            'nullable',
//                            'regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
//                            // Második és perc ne legyen 60+
//                            function (string $attribute, $value, \Closure $fail) {
//                                if (!$value) return;
//
//                                $parts = array_map('intval', explode(':', $value));
//
//                                if (count($parts) === 2) {
//                                    [$m, $s] = $parts;
//                                    if ($s > 59) $fail('A másodperc 0 és 59 között legyen.');
//                                } else {
//                                    [$h, $m, $s] = $parts;
//                                    if ($m > 59 || $s > 59) $fail('A perc és másodperc 0 és 59 között legyen.');
//                                }
//                            },
//                        ])
                        ->inputMode('numeric'),

                    Forms\Components\TextInput::make('price_huf')
                        ->label('Ár (HUF)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->suffix('Ft'),

                    Forms\Components\TextInput::make('original_price_huf')
                        ->label('Eredeti Ár (HUF)')
                        ->helperText('Akciós ár esetén kell csak megadni')
                        ->nullable()
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
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->withSum('accesses as total_sales_count', 'id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Cikkszám')
                    ->searchable()
                    ->sortable()
                    ->wrap(),


                Tables\Columns\TextColumn::make('title')
                    ->label('Cím')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('price_huf')
                    ->label('Ár')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' Ft'),

                Tables\Columns\TextColumn::make('total_sales_count')
                    ->label('Megvásárolva')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->columnSpanFull(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktív')
                    ->boolean()
                    ->sortable(),


                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->getStateUsing(fn ($record) => route('video.show', $record))
                    ->url(fn ($record) => route('video.show', $record))
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Létrehozva')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktív'),
                Tables\Filters\SelectFilter::make('video_type_id')
                    ->label('Típus')
                    ->relationship('type', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('video_domain_id')
                    ->label('Szakterület')
                    ->relationship('domain', 'name')
                    ->searchable()
                    ->preload(),
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
