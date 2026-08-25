<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseApplicationResource\Pages;
use App\Models\CourseApplication;
use App\Services\Moodle\ParticipantSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->course?->name.' ('.$record->start_date.' - '.$record->end_date.')')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Státusz')
                ->options([
                    'NEW' => 'Új',
                    'PROCESSED' => 'Feldolgozott',
                    //                    'SENT' => 'Fizetett',
                    'CANCELLED' => 'Lemondott',
                ])
                ->required(),

            Forms\Components\TextInput::make('certificate_language')->label('Tanúsítvány nyelve')->disabled(),

            Forms\Components\Section::make('Díjfizető')->columns(2)->schema([
                Forms\Components\TextInput::make('payer_name')->required(),
                Forms\Components\TextInput::make('payer_tax_number')->required(),
                Forms\Components\TextInput::make('payer_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('payer_mailing_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('payer_email')->required()->columnSpanFull(),
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

            Forms\Components\Section::make('Moodle szinkronizálás')
                ->visible(fn (?CourseApplication $record): bool => $record !== null)
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('moodle_status_display')
                        ->label('Állapot')
                        ->content(fn (?CourseApplication $record): HtmlString => new HtmlString(sprintf(
                            '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset %s">%s</span>',
                            static::moodleStatusClasses($record?->moodle_sync_status),
                            e(static::moodleStatusLabel($record?->moodle_sync_status)),
                        ))),
                    Forms\Components\Placeholder::make('moodle_user_id_display')
                        ->label('Moodle felhasználóazonosító')
                        ->content(fn (?CourseApplication $record): string => $record?->moodle_user_id !== null ? (string) $record->moodle_user_id : '—'),
                    Forms\Components\Placeholder::make('moodle_last_synced_at_display')
                        ->label('Utolsó sikeres szinkronizálás')
                        ->content(fn (?CourseApplication $record): string => $record?->moodle_last_synced_at?->format('Y-m-d H:i') ?? '—'),
                    Forms\Components\Placeholder::make('moodle_sync_error_display')
                        ->label('Utolsó hiba')
                        ->content(fn (?CourseApplication $record): string => filled($record?->moodle_sync_error) ? $record->moodle_sync_error : '—')
                        ->columnSpanFull(),
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
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'NEW' => 'Új',
                        'PROCESSED' => 'Feldolgozott',
                        'CANCELLED' => 'Lemondott',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'NEW' => 'info',
                        'PROCESSED' => 'success',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('moodle_sync_status')
                    ->label('Moodle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::moodleStatusLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        ParticipantSyncService::STATUS_PENDING => 'warning',
                        ParticipantSyncService::STATUS_SYNCED => 'success',
                        ParticipantSyncService::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beérkezett')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'NEW' => 'Új',
                    'PROCESSED' => 'Feldolgozott',
                    //                    'SENT' => 'Fizetett',
                    'CANCELLED' => 'Lemondott',
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

    public static function moodleStatusLabel(?string $status): string
    {
        return match ($status) {
            ParticipantSyncService::STATUS_PENDING => 'Függőben',
            ParticipantSyncService::STATUS_SYNCED => 'Szinkronizálva',
            ParticipantSyncService::STATUS_FAILED => 'Sikertelen',
            default => 'Még nem szinkronizált',
        };
    }

    private static function moodleStatusClasses(?string $status): string
    {
        return match ($status) {
            ParticipantSyncService::STATUS_PENDING => 'bg-warning-50 text-warning-700 ring-warning-600/20',
            ParticipantSyncService::STATUS_SYNCED => 'bg-success-50 text-success-700 ring-success-600/20',
            ParticipantSyncService::STATUS_FAILED => 'bg-danger-50 text-danger-700 ring-danger-600/20',
            default => 'bg-gray-50 text-gray-600 ring-gray-500/20',
        };
    }
}
