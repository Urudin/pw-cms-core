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
            $table->string('group_id')->nullable()->after('order');
            $table->string('group_classes')->nullable()->after('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            //
        });
    }
};
