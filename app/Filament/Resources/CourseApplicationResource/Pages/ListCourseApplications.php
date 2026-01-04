<?php

namespace App\Filament\Resources\CourseApplicationResource\Pages;

use App\Filament\Resources\CourseApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseApplications extends ListRecords
{
    protected static string $resource = CourseApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
