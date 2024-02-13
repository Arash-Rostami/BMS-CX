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
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->text('payer');
            $table->string('number');
            $table->string('beneficiary_name');
            $table->text('beneficiary_address')->nullable();
            $table->string('bank_name');
            $table->string('account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('IBAN')->nullable();
            $table->double('amount');
            $table->enum('currency', ['USD', 'EURO', 'Dirham', 'Ruble'])->default('USD');
            $table->string('IFSC')->nullable();
            $table->unsignedBigInteger('MICR')->nullable();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('order_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
