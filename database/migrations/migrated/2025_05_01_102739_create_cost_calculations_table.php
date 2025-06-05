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
        Schema::create('cost_calculations', function (Blueprint $table) {
            // Primary key
            $table->increments('id')->unsigned()->index();
            // Excel file columns
            $table->string('tender_no')->nullable();
            $table->date('date')->nullable();
            $table->date('validity')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('term')->nullable();
            $table->decimal('win_price_usd', 10, 2)->nullable();
            $table->decimal('persol_price_usd', 10, 2)->nullable();
            $table->decimal('price_difference', 10, 2)->nullable();
            $table->decimal('cfr_china', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            // Cost-related columns
            $table->string('transport_type')->nullable();
            $table->decimal('transport_cost', 10, 2)->nullable();
            $table->string('container_type')->nullable();
            $table->decimal('thc_cost', 10, 2)->nullable();
            $table->decimal('stuffing_cost', 10, 2)->nullable();
            $table->decimal('ocean_freight', 10, 2)->nullable();
            $table->decimal('exchange_rate', 15, 2);
            // Flexible JSON columns
            $table->json('extra')->nullable();
            $table->json('additional_costs')->nullable();
            // Calculated total
            $table->decimal('total_cost', 15, 2)->nullable();
            // Foreign keys defined explicitly
            $table->integer('product_id')->unsigned()->index();
            $table->foreign('product_id')->references('id')->on('products');
            $table->integer('grade_id')->unsigned()->index();
            $table->foreign('grade_id')->references('id')->on('grades');
            $table->integer('supplier_id')->unsigned()->index();
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->integer('packaging_id')->unsigned()->index();
            $table->foreign('packaging_id')->references('id')->on('packagings');
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_calculations');
    }
};
