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
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->string('wrap_section_start')->nullable()->change();
            $table->string('wrap_section_close')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->string('wrap_section_start')->change();
            $table->string('wrap_section_close')->change();
        });
    }
};
