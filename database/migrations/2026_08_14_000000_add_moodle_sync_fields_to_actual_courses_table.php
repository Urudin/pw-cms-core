<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actual_courses', function (Blueprint $table) {
            $table->unsignedBigInteger('moodle_course_id')->nullable()->after('id');
            $table->string('moodle_sync_status')->nullable()->after('moodle_course_id');
            $table->timestamp('moodle_last_synced_at')->nullable()->after('moodle_sync_status');
            $table->text('moodle_sync_error')->nullable()->after('moodle_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('actual_courses', function (Blueprint $table) {
            $table->dropColumn([
                'moodle_course_id',
                'moodle_sync_status',
                'moodle_last_synced_at',
                'moodle_sync_error',
            ]);
        });
    }
};
