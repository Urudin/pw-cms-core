<?php

namespace App\Filament\Resources\CourseApplicationResource\Pages;

use App\Filament\Resources\CourseApplicationResource;
use App\Services\Moodle\ParticipantSyncDispatcher;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCourseApplication extends EditRecord
{
    protected static string $resource = CourseApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncMoodle')
                ->label(fn (): string => $this->record->moodle_user_id === null ? 'Moodle szinkronizálás' : 'Moodle újraszinkronizálás')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => (bool) config('moodle.enabled') && in_array($this->record->status, ['PROCESSED', 'CANCELLED'], true))
                ->action(function (): void {
                    app(ParticipantSyncDispatcher::class)->dispatch($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('A Moodle résztvevő-szinkronizálás sorba állítva.')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
