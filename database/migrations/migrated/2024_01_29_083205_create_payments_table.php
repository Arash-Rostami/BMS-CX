<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->text('payer');
            $table->double('amount');
            $table->enum('currency', ['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial'])->default('USD');
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('payment_request_id')->unsigned()->index()->nullable();
            $table->integer('order_id')->unsigned()->index()->nullable();
            $table->integer('attachment_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('attachment_id')->references('id')->on('attachments');
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
