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
        Schema::create('stocks', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->double('buying_quantity')->default(0);
            $table->double('initial_quantity')->nullable();
            $table->double('provisional_quantity')->nullable();
            $table->double('final_quantity')->nullable();
            $table->decimal('buying_price',12,4);
            $table->decimal('initial_price',12,4)->nullable();
            $table->decimal('provisional_price',12,4)->nullable();
            $table->decimal('final_price',12,4)->nullable();
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
        Schema::dropIfExists('stocks');
    }
};
