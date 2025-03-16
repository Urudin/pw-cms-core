<?php

namespace App\Filament\Resources\UserSettingResource\Pages;

use App\Filament\Resources\UserSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserSettings extends ListRecords
{
    protected static string $resource = UserSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
