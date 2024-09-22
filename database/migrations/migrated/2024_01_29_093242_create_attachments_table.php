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
        Schema::create('attachments', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('name')->nullable()->index();
            $table->string('file_path')->nullable()->index();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('order_id')->unsigned()->index()->nullable();
            $table->integer('payment_id')->unsigned()->index()->nullable();
            $table->integer('payment_request_id')->unsigned()->index()->nullable();
            $table->integer('proforma_invoice_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('payment_id')->references('id')->on('payments');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests');
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
