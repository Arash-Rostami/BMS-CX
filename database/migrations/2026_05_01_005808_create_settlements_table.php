<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_entry_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date');
            $table->integer('duration_days');
            $table->decimal('foreign_settlement_amount', 24, 6)->nullable();
            $table->decimal('settlement_exchange_rate', 24, 6)->nullable();
            $table->decimal('settlement_amount', 24, 6);
            $table->decimal('accrued_interest_in_period', 24, 6);
            $table->decimal('deducted_from_interest', 24, 6)->default(0);
            $table->decimal('deducted_from_principal', 24, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
