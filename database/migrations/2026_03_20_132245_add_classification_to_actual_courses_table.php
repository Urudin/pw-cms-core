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
        Schema::table('actual_courses', function (Blueprint $table) {
            $table->string('classification')->nullable()->after('course_id');
            $table->unsignedBigInteger('user_given_id')->nullable()->after('classification');
            $table->renameColumn('type', 'way_of_participation');
            $table->smallInteger('min_participants')->default(0)->after('max_participants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actual_courses', function (Blueprint $table) {
            $table->dropColumn('classification');
            $table->dropColumn('user_given_id');
            $table->dropColumn('min_participants');
            $table->renameColumn('way_of_participation', 'type');
        });
    }
};
