<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseApplicationResource\Pages;
use App\Models\CourseApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseApplicationResource extends Resource
{
    protected static ?string $model = CourseApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Tanfolyamok';
    protected static ?string $navigationLabel = 'Jelentkezések';
    protected static ?string $pluralModelLabel = 'Jelentkezések';
    protected static ?string $modelLabel = 'Jelentkezés';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('actual_course_id')
                ->label('Aktuális tanfolyam')
                ->relationship('actualCourse', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->course?->name . ' (' . $record->start_date . ' - ' . $record->end_date . ')')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Státusz')
                ->options([
                    'NEW' => 'NEW',
                    'PROCESSED' => 'PROCESSED',
                    'SENT' => 'SENT',
                    'CANCELLED' => 'CANCELLED',
                ])
                ->required(),

            Forms\Components\TextInput::make('certificate_language')->label('Tanúsítvány nyelve')->disabled(),

            Forms\Components\Section::make('Díjfizető')->columns(2)->schema([
                Forms\Components\TextInput::make('payer_name')->required(),
                Forms\Components\TextInput::make('payer_tax_number')->required(),
                Forms\Components\TextInput::make('payer_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('payer_mailing_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('payer_signatory')->required()->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Résztvevő')->columns(2)->schema([
                Forms\Components\TextInput::make('participant_last_name')->required(),
                Forms\Components\TextInput::make('participant_first_name')->required(),
                Forms\Components\TextInput::make('participant_birth_name')->required(),
                Forms\Components\TextInput::make('participant_birth_place')->required(),
                Forms\Components\TextInput::make('participant_birth_country')->required(),
                Forms\Components\TextInput::make('participant_birth_date')->required(),
                Forms\Components\TextInput::make('participant_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('participant_notification_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('participant_phone')->required(),
                Forms\Components\TextInput::make('participant_email')->email()->required(),
                Forms\Components\TextInput::make('participant_mother_name')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('participant_education'),
                Forms\Components\TextInput::make('participant_education_id'),
                Forms\Components\Select::make('participant_supported')
                    ->options(['igen' => 'igen', 'nem' => 'nem']),
                Forms\Components\TextInput::make('participant_grant_id'),
            ]),

            Forms\Components\Section::make('Hozzájárulások')->columns(2)->schema([
                Forms\Components\Toggle::make('newsletter_opt_in')->label('Hírlevél'),
                Forms\Components\Toggle::make('privacy_accepted')->label('Adatkezelés elfogadva'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('actualCourse.course.name')
                    ->label('Kurzus')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('actualCourse.start_date')
                    ->label('Kezdés')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('participant_email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payer_name')
                    ->label('Díjfizető')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beérkezett')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'NEW' => 'NEW',
                    'PROCESSED' => 'PROCESSED',
                    'SENT' => 'SENT',
                    'CANCELLED' => 'CANCELLED',
                ]),
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
            'index' => Pages\ListCourseApplications::route('/'),
            'create' => Pages\CreateCourseApplication::route('/create'),
            'edit' => Pages\EditCourseApplication::route('/{record}/edit'),
        ];
    }
}
