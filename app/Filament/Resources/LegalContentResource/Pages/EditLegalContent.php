<?php

namespace App\Filament\Resources\LegalContentResource\Pages;

use App\Filament\Resources\LegalContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLegalContent extends EditRecord
{
    protected static string $resource = LegalContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
