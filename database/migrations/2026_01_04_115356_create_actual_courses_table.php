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
        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['group', 'individual']);
            $table->smallInteger('max_participants')->default(15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actual_courses');
    }
};
