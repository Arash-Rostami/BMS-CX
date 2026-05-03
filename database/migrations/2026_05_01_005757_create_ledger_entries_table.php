<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->integer('account_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->index();
            $table->enum('type', ['disbursement', 'receipt']);
            $table->string('description');
            $table->date('transaction_date');
            $table->string('currency_type');
            $table->decimal('amount_foreign_currency', 24, 6)->nullable();
            $table->decimal('exchange_rate', 24, 6)->nullable();
            $table->decimal('principal_amount_base', 24, 6);
            $table->decimal('commission_amount', 24, 6)->default(0);
            $table->decimal('total_disbursed_base', 24, 6);
            $table->json('rate_matrix_snapshot')->nullable();
            $table->decimal('remaining_principal', 24, 6)->nullable();
            $table->decimal('unpaid_interest', 24, 6)->default(0);
            $table->boolean('is_settled')->default(false);

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
