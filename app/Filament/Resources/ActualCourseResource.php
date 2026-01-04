<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActualCourseResource\Pages;
use App\Models\ActualCourse;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActualCourseResource extends Resource
{
    protected static ?string $model = ActualCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Képzések';

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

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Kezdés')
                        ->required(),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Befejezés')
                        ->required()
                        ->afterOrEqual('start_date'),

                    Forms\Components\Select::make('type')
                        ->label('Típus')
                        ->options([
                            'group' => 'Csoportos',
                            'individual' => 'Egyéni',
                        ])
                        ->required(),

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
                                ->required(),
                        ])
                        ->addActionLabel('Új nap')
                        ->reorderable()
                        ->defaultItems(0)
                        ->columns(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Kurzus')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Típus')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'group' ? 'Csoportos' : 'Egyéni'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Kezdés')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Befejezés')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_participants')
                    ->label('Max')
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

                Tables\Filters\SelectFilter::make('type')
                    ->label('Típus')
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
