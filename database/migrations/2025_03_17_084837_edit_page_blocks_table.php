<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->dropForeign(['block_id']); // Töröljük a külső kulcsot
            $table->dropColumn('block_id'); // Töröljük a block_id mezőt
        });
    }

    public function down(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
        });
    }
};
