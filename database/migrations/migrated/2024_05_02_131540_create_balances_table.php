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
        Schema::create('balances', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->enum('currency', ['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial'])->default('USD');
            $table->double('base')->nullable();
            $table->double('payment')->nullable();
            $table->double('total')->nullable();
            $table->string('category');
            $table->string('category_id');
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('department_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
