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
        Schema::table('video_accesses', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->constrained();
            $table->timestamp('first_used_at')->nullable();
            $table->integer('usage_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_accesses', function (Blueprint $table) {
            $table->dropForeign('video_accesses_purchase_id_foreign');
            $table->dropColumn('purchase_id');
            $table->dropColumn('first_used_at');
            $table->dropColumn('usage_count');
        });
    }
};
