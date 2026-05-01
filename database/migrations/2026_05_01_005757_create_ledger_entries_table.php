<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['disbursement', 'receipt']);
            $table->string('description');
            $table->date('transaction_date');
            $table->string('currency_type');
            $table->decimal('amount_foreign_currency', 24, 6);
            $table->decimal('exchange_rate', 24, 6);
            $table->decimal('principal_amount_base', 24, 6);
            $table->decimal('commission_amount', 24, 6)->default(0);
            $table->decimal('total_disbursed_base', 24, 6);
            $table->json('rate_matrix_snapshot')->nullable();
            $table->decimal('remaining_principal', 24, 6);
            $table->decimal('unpaid_interest', 24, 6)->default(0);
            $table->boolean('is_settled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
