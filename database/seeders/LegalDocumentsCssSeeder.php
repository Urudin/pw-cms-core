<?php

namespace Database\Seeders;

use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LegalDocumentsCssSeeder extends Seeder
{
    public function run(): void
    {
        $cssPath = public_path('css/data-handling-refactored.css');

        if (! File::exists($cssPath)) {
            $this->command?->warn("CSS file not found: {$cssPath}");
            return;
        }

        UserSetting::query()->updateOrCreate(
            ['name' => 'legal-documents-css'],
            ['value' => File::get($cssPath)]
        );
    }
}
