<?php

namespace App\Filament\Resources\ActualCourseResource\Pages;

use App\Filament\Resources\ActualCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActualCourses extends ListRecords
{
    protected static string $resource = ActualCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
