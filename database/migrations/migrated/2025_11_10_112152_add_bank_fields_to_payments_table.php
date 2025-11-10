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
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('bank_charges', 15, 2)->nullable()->after('amount');
            $table->decimal('exchange_rate', 10, 6)->nullable()->after('bank_charges');
            $table->decimal('equivalent_amount', 15, 2)->nullable()->after('exchange_rate');
            $table->string('bank_charges_currency', 18)->nullable()->after('equivalent_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
