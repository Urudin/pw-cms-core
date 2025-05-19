<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Block; // vagy a megfelelő modelled

class GenerateTailwindBlocks extends Command
{
    protected $signature = 'tailwind:generate-blocks';
    protected $description = 'Generál Blade fájlokat a blokkokhoz a Tailwind számára';

    public function handle()
    {
        $blocks = Block::all();

        $directory = storage_path('tailwind-blocks');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($blocks as $block) {
            $fileName = "tailwindBlock-{$block->id}.blade.php";
            $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

            file_put_contents($filePath, $block->content); // vagy amit szeretnél
        }

        $this->info('Tailwind block fájlok sikeresen generálva.');
    }
}
