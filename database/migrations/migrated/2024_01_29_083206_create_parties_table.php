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
        Schema::create('parties', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('order_id')->unsigned()->index()->nullable();
            $table->integer('packaging_id')->unsigned()->index()->nullable();
            $table->integer('buyer_id')->unsigned()->index()->nullable();
            $table->integer('supplier_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('packaging_id')->references('id')->on('packagings');
            $table->foreign('buyer_id')->references('id')->on('buyers');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
