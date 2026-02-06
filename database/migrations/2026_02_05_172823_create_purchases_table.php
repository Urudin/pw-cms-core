<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // personal
            $table->string('personal_last_name', 200);
            $table->string('personal_first_name', 200);
            $table->string('personal_phone', 50);
            $table->string('personal_email', 255);
            $table->text('personal_note')->nullable();

            // billing
            $table->string('billing_last_name', 200);
            $table->string('billing_first_name', 200);
            $table->string('billing_company_name', 255)->nullable();
            $table->string('billing_vat_number', 100)->nullable();
            $table->string('billing_address', 500);

            // payment
            $table->string('payment_method', 100);

            // declarations
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('privacy_accepted')->default(false);
            $table->boolean('newsletter_opt_in')->default(false);
            $table->boolean('new_video_opt_in')->default(false);

            // items and totals
            $table->json('items'); // [{id, quantity, ... optional}]
            $table->string('currency', 10)->default('HUF');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);    // pl. 27.00
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // meta
            $table->string('status', 50)->default('pending'); // pending/paid/failed/cancelled
            $table->string('client_ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
