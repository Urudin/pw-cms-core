<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActualCourseResource\Pages;
use App\Models\ActualCourse;
use App\Services\Moodle\CourseSyncService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ActualCourseResource extends Resource
{
    protected static ?string $model = ActualCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Tanfolyamok';

    protected static ?string $navigationLabel = 'Aktuális Tanfolyamok';

    protected static ?string $pluralModelLabel = 'Aktuális Tanfolyamok';

    protected static ?string $modelLabel = 'Aktuális Tanfolyam';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Turnus adatok')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('course_id')
                        ->label('Kurzus')
                        ->relationship('course', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('classification')
                        ->label('Tanfolyam besorolása')
                        ->options([
                            'Minősített tanfolyami oktatás' => 'Minősített tanfolyami oktatás',
                            'Minősített oktatás' => 'Minősített oktatás',
                            'Nem minősített oktatás' => 'Nem minősített oktatás',
                        ])
                        ->nullable(),

                    Forms\Components\TextInput::make('user_given_id')
                        ->label('Egyedi Azonosító')
                        ->numeric()
                        ->rule('min:1')
                        ->required(),

                    Forms\Components\TextInput::make('place_of_event')
                        ->label('Oktatás helyszíne')
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Kezdés')
                        ->required(),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Befejezés')
                        ->required()
                        ->afterOrEqual('start_date'),

                    Forms\Components\DatePicker::make('application_deadline')
                        ->label('Jelentkezési határidő')
                        ->required(),

                    Forms\Components\Select::make('way_of_participation')
                        ->label('Részvételi mód')
                        ->options([
                            'group' => 'Csoportos',
                            'individual' => 'Egyéni',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('price')
                        ->label('Tanfolyam díja')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(0),

                    Forms\Components\TextInput::make('min_participants')
                        ->label('Min. résztvevő')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(0),

                    Forms\Components\TextInput::make('max_participants')
                        ->label('Max. résztvevő')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(15),

                    Repeater::make('days')
                        ->label('Oktatási napok')
                        ->relationship('days')
                        ->schema([
                            DatePicker::make('day')
                                ->label('Nap')
                                ->columnSpanFull()
                                ->required(),
                            TimePicker::make('start_time')
                                ->label('Kezdés időpontja')
                                ->seconds(false)
                                ->format('H:i')
                                ->default('09:00:00')
                                ->required(),

                            TimePicker::make('end_time')
                                ->label('Befejezés időpontja')
                                ->seconds(false)
                                ->format('H:i')
                                ->default('13:00:00')
                                ->required(),
                        ])
                        ->addActionLabel('Új nap')
                        ->reorderable()
                        ->columns()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Moodle szinkronizálás')
                ->visible(fn (?ActualCourse $record): bool => $record !== null)
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('moodle_status_display')
                        ->label('Állapot')
                        ->content(fn (?ActualCourse $record): HtmlString => new HtmlString(sprintf(
                            '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset %s">%s</span>',
                            static::moodleStatusClasses($record?->moodle_sync_status),
                            e(static::moodleStatusLabel($record?->moodle_sync_status)),
                        ))),
                    Forms\Components\Placeholder::make('moodle_course_id_display')
                        ->label('Moodle kurzusazonosító')
                        ->content(fn (?ActualCourse $record): string => $record?->moodle_course_id !== null ? (string) $record->moodle_course_id : '—'),
                    Forms\Components\Placeholder::make('moodle_last_synced_at_display')
                        ->label('Utolsó sikeres szinkronizálás')
                        ->content(fn (?ActualCourse $record): string => $record?->moodle_last_synced_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('moodle_sync_error_display')
                        ->label('Utolsó hiba')
                        ->content(fn (?ActualCourse $record): string => filled($record?->moodle_sync_error) ? $record->moodle_sync_error : '—')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_given_id')
                    ->label('Azonosító')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('course.name')
                    ->label('Kurzus')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('way_of_participation')
                    ->label('Részvételi mód')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'group' ? 'Csoportos' : 'Egyéni'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Kezdés')
                    ->date('Y-m-d')
                    ->badge()
                    ->color(fn ($state) => filled($state) && Carbon::parse($state)->lt(today()) ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Befejezés')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('application_deadline')
                    ->label('Jelentkezési határidő')
                    ->date('Y-m-d')
                    ->badge()
                    ->color(fn ($state) => filled($state) && Carbon::parse($state)->lt(today()) ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('moodle_sync_status')
                    ->label('Moodle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::moodleStatusLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        CourseSyncService::STATUS_PENDING => 'warning',
                        CourseSyncService::STATUS_SYNCED => 'success',
                        CourseSyncService::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('place_of_event')
                    ->label('Oktatás helyszíne')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_participants')
                    ->label('Max')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Tanfolyam díja')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Módosítva')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Kurzus')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('way_of_participation')
                    ->label('Részvételi mód')
                    ->options([
                        'group' => 'Csoportos',
                        'individual' => 'Egyéni',
                    ]),
            ])
            ->defaultSort('start_date', 'desc')
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

    public static function moodleStatusLabel(?string $status): string
    {
        return match ($status) {
            CourseSyncService::STATUS_PENDING => 'Függőben',
            CourseSyncService::STATUS_SYNCED => 'Szinkronizálva',
            CourseSyncService::STATUS_FAILED => 'Sikertelen',
            default => 'Még nem szinkronizált',
        };
    }

    private static function moodleStatusClasses(?string $status): string
    {
        return match ($status) {
            CourseSyncService::STATUS_PENDING => 'bg-warning-50 text-warning-700 ring-warning-600/20',
            CourseSyncService::STATUS_SYNCED => 'bg-success-50 text-success-700 ring-success-600/20',
            CourseSyncService::STATUS_FAILED => 'bg-danger-50 text-danger-700 ring-danger-600/20',
            default => 'bg-gray-50 text-gray-600 ring-gray-500/20',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActualCourses::route('/'),
            'create' => Pages\CreateActualCourse::route('/create'),
            'edit' => Pages\EditActualCourse::route('/{record}/edit'),
        ];
    }
}
