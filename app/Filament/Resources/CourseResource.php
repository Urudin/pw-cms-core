<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Tanfolyamok';
    protected static ?string $navigationLabel = 'Tanfolyamok';
    protected static ?string $pluralModelLabel = 'Tanfolyamok';
    protected static ?string $modelLabel = 'Tanfolyam';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Alapadatok')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('course_category_id')
                        ->label('Kategória')
                        ->relationship('courseCategory', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktív')
                        ->default(true),

                    Forms\Components\TextInput::make('name')
                        ->label('Kurzus neve')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('price')
                        ->label('Ár')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->suffix('Ft'),
                ]),

            Forms\Components\Section::make('Leírás')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->label('HTML leírás')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'link',
                            'blockquote',
                            'orderedList',
                            'bulletList',
                            'h2',
                            'h3',
                            'redo',
                            'undo',
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kurzus')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('courseCategory.name')
                    ->label('Kategória')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktív')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Ár')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' Ft'),

                Tables\Columns\TextColumn::make('actual_courses_count')
                    ->label('Turnusok')
                    ->counts('actualCourses')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Módosítva')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktív státusz'),
                Tables\Filters\SelectFilter::make('course_category_id')
                    ->label('Kategória')
                    ->relationship('courseCategory', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('updated_at', 'desc')
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
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
