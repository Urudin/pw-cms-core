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
            $table->string('billing_postal_code', 20)->nullable()->after('billing_address');
            $table->string('billing_city', 120)->nullable()->after('billing_postal_code');
            $table->string('billing_street_address', 255)->nullable()->after('billing_city');
            $table->boolean('billed')->default(false)->after('billing_street_address');
            $table->dropColumn('billing_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['billing_postal_code', 'billing_city', 'billing_street_address']);
        });
    }
};
