<?php

namespace App\Filament\Resources\ActualCourseResource\Pages;

use App\Filament\Resources\ActualCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActualCourse extends EditRecord
{
    protected static string $resource = ActualCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
