<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actual_course_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->timestamps();

            $table->unique(['actual_course_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actual_course_days');
    }
};
