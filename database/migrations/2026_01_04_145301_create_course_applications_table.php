<?php

// php artisan make:migration create_course_applications_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actual_course_id')->constrained()->cascadeOnDelete();

            // Képzéshez kapcsolódó mezők
            $table->string('certificate_language')->nullable(); // Angol / Német

            // Díjfizető
            $table->string('payer_name');
            $table->string('payer_address');
            $table->string('payer_mailing_address');
            $table->string('payer_signatory');
            $table->string('payer_tax_number');

            // Résztvevő
            $table->string('participant_last_name');
            $table->string('participant_first_name');
            $table->string('participant_birth_name');
            $table->string('participant_birth_place');
            $table->string('participant_birth_country');
            $table->string('participant_birth_date'); // egyszerűen string (később lehet date)
            $table->string('participant_address');
            $table->string('participant_notification_address');
            $table->string('participant_phone');
            $table->string('participant_email');
            $table->string('participant_mother_name');
            $table->string('participant_education')->nullable();
            $table->string('participant_education_id')->nullable();
            $table->enum('participant_supported', ['igen', 'nem'])->nullable();
            $table->string('participant_grant_id')->nullable();

            // hozzájárulások
            $table->boolean('newsletter_opt_in')->default(false);
            $table->boolean('privacy_accepted')->default(false);

            // admin státusz
            $table->enum('status', ['NEW', 'PROCESSED', 'SENT', 'CANCELLED'])->default('NEW');

            // spam honeypot
            $table->string('robot_field')->nullable();

            $table->timestamps();

            $table->index(['actual_course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_applications');
    }
};

