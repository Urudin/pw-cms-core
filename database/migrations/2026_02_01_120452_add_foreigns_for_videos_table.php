<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('video_type_id')->nullable()->constrained('video_types')->nullOnDelete();
            $table->foreignId('video_topic_id')->nullable()->constrained('video_topics')->nullOnDelete();
            $table->foreignId('video_domain_id')->nullable()->constrained('video_domains')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
