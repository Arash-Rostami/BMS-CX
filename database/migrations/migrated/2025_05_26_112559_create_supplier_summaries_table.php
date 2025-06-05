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
        Schema::create('supplier_summaries', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_id')->unsigned()->index();
            $table->integer('proforma_invoice_id')->unsigned()->index()->nullable();
            $table->enum('type', ['proforma', 'adjustment'])->default('proforma');
            $table->string('contract_number')->nullable();
            $table->string('currency', 10)->index();
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('expected', 15, 2)->default(0);
            $table->decimal('diff', 15, 2)->default(0);
            $table->string('status', 20);
            $table->timestamps();

            $table->foreign('supplier_id')
                ->references('id')->on('suppliers')
                ->onDelete('cascade');


            $table->foreign('proforma_invoice_id')
                ->references('id')->on('proforma_invoices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_summaries');
    }
};
