<?php

namespace App\Filament\Resources\UserSettingResource\Pages;

use App\Filament\Resources\UserSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserSetting extends CreateRecord
{
    protected static string $resource = UserSettingResource::class;
}
