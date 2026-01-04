<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseCategoryResource\Pages;
use App\Models\CourseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseCategoryResource extends Resource
{
    protected static ?string $model = CourseCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Képzések';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Név')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Név')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('courses_count')
                    ->label('Kurzusok')
                    ->counts('courses')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Módosítva')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
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
            'index' => Pages\ListCourseCategories::route('/'),
            'create' => Pages\CreateCourseCategory::route('/create'),
            'edit' => Pages\EditCourseCategory::route('/{record}/edit'),
        ];
    }
}
