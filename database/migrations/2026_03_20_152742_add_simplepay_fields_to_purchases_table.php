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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_transaction_id')->nullable()->index()->after('payment_provider');
            $table->timestamp('payment_started_at')->nullable()->after('payment_transaction_id');
            $table->timestamp('payment_paid_at')->nullable()->after('payment_started_at');
            $table->timestamp('payment_failed_at')->nullable()->after('payment_paid_at');
            $table->json('payment_payload')->nullable()->after('payment_failed_at');
            $table->text('payment_error_message')->nullable()->after('payment_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            //
        });
    }
};
