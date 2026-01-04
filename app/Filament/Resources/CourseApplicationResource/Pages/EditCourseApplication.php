<?php

namespace App\Filament\Resources\CourseApplicationResource\Pages;

use App\Filament\Resources\CourseApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseApplication extends EditRecord
{
    protected static string $resource = CourseApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
