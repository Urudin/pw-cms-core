<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActualCourseResource\Pages;
use App\Models\ActualCourse;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActualCourses::route('/'),
            'create' => Pages\CreateActualCourse::route('/create'),
            'edit' => Pages\EditActualCourse::route('/{record}/edit'),
        ];
    }
}
