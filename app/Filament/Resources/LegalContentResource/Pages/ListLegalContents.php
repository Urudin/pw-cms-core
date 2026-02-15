<?php

namespace App\Filament\Resources\LegalContentResource\Pages;

use App\Filament\Resources\LegalContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLegalContents extends ListRecords
{
    protected static string $resource = LegalContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
