<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('legal_contents', function (Blueprint $table) {
            $table->string('url')->nullable()->after('name');
        });

        $usedUrls = [];

        DB::table('legal_contents')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($legalContent) use (&$usedUrls) {
                $url = match ((int) $legalContent->id) {
                    1 => 'adatkezelesi-tajekoztato-kepzes',
                    2 => 'innovacio-menedzsment-aszf',
                    default => Str::slug((string) $legalContent->name) ?: 'legal-content-' . $legalContent->id,
                };

                $baseUrl = $url;
                $suffix = 2;

                while (in_array($url, $usedUrls, true)) {
                    $url = $baseUrl . '-' . $suffix;
                    $suffix++;
                }

                $usedUrls[] = $url;

                DB::table('legal_contents')
                    ->where('id', $legalContent->id)
                    ->update(['url' => $url]);
            });

        Schema::table('legal_contents', function (Blueprint $table) {
            $table->unique('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legal_contents', function (Blueprint $table) {
            $table->dropUnique(['url']);
            $table->dropColumn('url');
        });
    }
};
