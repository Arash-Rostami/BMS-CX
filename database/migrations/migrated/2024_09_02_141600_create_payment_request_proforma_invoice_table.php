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
        Schema::create('payment_request_proforma_invoice', function (Blueprint $table) {
            $table->unsignedInteger('payment_request_id');
            $table->unsignedInteger('proforma_invoice_id');

            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->onDelete('cascade');
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->onDelete('cascade');
            $table->primary(['payment_request_id', 'proforma_invoice_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_request_proforma_invoice');
    }
};
