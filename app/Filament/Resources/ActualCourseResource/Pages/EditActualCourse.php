<?php

namespace App\Filament\Resources\ActualCourseResource\Pages;

use App\Filament\Resources\ActualCourseResource;
use App\Services\Moodle\CourseSyncDispatcher;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditActualCourse extends EditRecord
{
    protected static string $resource = ActualCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncMoodle')
                ->label(fn (): string => $this->record->moodle_course_id === null ? 'Moodle szinkronizálás' : 'Moodle újraszinkronizálás')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => (bool) config('moodle.enabled'))
                ->action(function (): void {
                    app(CourseSyncDispatcher::class)->dispatch($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('A Moodle szinkronizálás sorba állítva.')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
