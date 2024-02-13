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
        Schema::create('order_requests', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('grade')->nullable();
            $table->string('quantity')->nullable();
            $table->string('price')->nullable();
            $table->json('details')->nullable();
            $table->enum('request_status', ['pending', 'review', 'approved', 'rejected', 'fulfilled'])->default('pending');
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('category_id')->unsigned()->index()->nullable();
            $table->integer('product_id')->unsigned()->index()->nullable();
            $table->integer('buyer_id')->unsigned()->index()->nullable();
            $table->integer('supplier_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('buyer_id')->references('id')->on('buyers');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_requests');
    }
};
