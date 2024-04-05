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
            $table->string('name')->nullable();
            $table->string('file_path')->nullable();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('order_id')->unsigned()->index()->nullable();
            $table->integer('payment_id')->unsigned()->index()->nullable();
            $table->integer('payment_request_id')->unsigned()->index()->nullable();
            $table->integer('doc_id')->unsigned()->index()->nullable();
            $table->integer('order_request_id')->unsigned()->index()->nullable();
            $table->integer('logistic_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('payment_id')->references('id')->on('payments');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests');
            $table->foreign('doc_id')->references('id')->on('docs');
            $table->foreign('order_request_id')->references('id')->on('order_requests');
            $table->foreign('logistic_id')->references('id')->on('logistics');
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
