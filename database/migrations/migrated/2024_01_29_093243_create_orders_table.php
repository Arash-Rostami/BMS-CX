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
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('order_number');
            $table->string('invoice_number');
            $table->unsignedBigInteger('part');
            $table->string('grade')->nullable();
            $table->string('proforma_number');
            $table->date('proforma_date');
            $table->string('order_status');
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('order_request_id')->unsigned()->index()->nullable();
            $table->integer('user_id')->unsigned()->index();
            $table->integer('purchase_status_id')->unsigned()->index()->nullable();
            $table->integer('category_id')->unsigned()->index()->nullable();
            $table->integer('product_id')->unsigned()->index()->nullable();
            $table->integer('order_detail_id')->unsigned()->index()->nullable();
            $table->integer('party_id')->unsigned()->index()->nullable();
            $table->integer('logistic_id')->unsigned()->index()->nullable();
            $table->integer('doc_id')->unsigned()->index()->nullable();
            $table->integer('attachment_id')->unsigned()->index()->nullable();
            $table->foreign('order_request_id')->references('id')->on('order_requests');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('purchase_status_id')->references('id')->on('purchase_statuses');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('order_detail_id')->references('id')->on('order_details');
            $table->foreign('party_id')->references('id')->on('parties');
            $table->foreign('logistic_id')->references('id')->on('logistics');
            $table->foreign('doc_id')->references('id')->on('docs');
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
        Schema::dropIfExists('orders');
    }
};
