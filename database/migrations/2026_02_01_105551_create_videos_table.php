<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            $table->string('title', 200);
            $table->text('description')->nullable();

            // Ha linkelt videót adsz el (Vimeo/YouTube/private link, stb.)
            $table->string('video_url', 2048);

            // Ár HUF-ban (egész szám). Ha fillért is akarsz, nevezd price_huf_cents-re.
            $table->unsignedInteger('price_huf')->default(0);

            $table->boolean('is_active')->default(true);
            $table->string('thumbnail_url', 2048)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
