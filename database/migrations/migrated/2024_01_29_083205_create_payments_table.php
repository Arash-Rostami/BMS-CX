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
            $table->string('reference_number')->nullable()->index();
            $table->text('payer');
            $table->double('amount');
            $table->enum('currency', ['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial'])->default('USD');
            $table->string('transaction_id')->nullable();
            $table->timestamp('date')->useCurrent();
            $table->text('notes');
            $table->text('payment_request')->nullable();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('order_id')->unsigned()->index()->nullable();
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
