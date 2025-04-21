<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReplaceUrlInContentTable extends Command
{
    protected $signature = 'db:replace-url {oldUrl} {newUrl}';
    protected $description = 'Kifejezetten a content mezőben cseréli le az URL-t a megadott táblában.';

    public function handle(): void
    {
        $oldUrl = $this->argument('oldUrl');
        $newUrl = $this->argument('newUrl');

        $table = 'blocks'; // <-- Itt állítsd be a konkrét tábla nevét
        $rows = DB::table($table)
            ->select('id', 'content')
            ->where('content', 'LIKE', '%' . $oldUrl . '%')
            ->get();

        foreach ($rows as $row) {
            $original = $row->content;
            $replaced = str_replace($oldUrl, $newUrl, $original);

            if ($original !== $replaced) {
                DB::table($table)
                    ->where('id', $row->id)
                    ->update(['content' => $replaced]);

                $this->info("✔ ID #{$row->id} frissítve.");
            }
        }

        $this->info("✅ Befejezve: '$oldUrl' → '$newUrl' cserélve a `content` mezőben.");
    }
}
