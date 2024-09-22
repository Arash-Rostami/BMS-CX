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
        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->decimal('buying_quantity', 12, 2)->default(0);
            $table->decimal('initial_quantity', 12, 2)->nullable();
            $table->decimal('provisional_quantity', 12, 2)->nullable();
            $table->decimal('final_quantity', 12, 2)->nullable();
            $table->decimal('buying_price', 12, 2)->default(0);
            $table->decimal('initial_price', 12, 2)->nullable();
            $table->decimal('provisional_price', 12, 2)->nullable();
            $table->decimal('final_price', 12, 2)->nullable();
            $table->json('extra')->nullable()->index();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
