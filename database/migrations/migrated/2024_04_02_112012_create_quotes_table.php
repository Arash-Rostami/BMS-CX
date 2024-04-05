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
        Schema::create('quotes', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('transportation_means', 255)->nullable();
            $table->string('transportation_type', 255)->nullable();
            $table->string('origin_port', 255);
            $table->string('destination_port', 255);
            $table->string('offered_rate', 255);
            $table->string('switch_bl_fee', 255)->nullable();
            $table->string('commodity_type', 255)->nullable();
            $table->string('packing_type', 255)->nullable();
            $table->string('payment_terms', 255)->nullable();
            $table->integer('free_time_pol');
            $table->integer('free_time_pod');
            $table->date('validity')->nullable();
            $table->text('extra')->nullable();
            // Foreign keys
            $table->integer('quote_request_id')->unsigned()->index();
            $table->integer('quote_provider_id')->unsigned()->index();
            $table->integer('attachment_id')->unsigned()->index()->nullable();
            $table->foreign('quote_request_id')->references('id')->on('quote_requests');
            $table->foreign('quote_provider_id')->references('id')->on('quote_providers');
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
        Schema::dropIfExists('quotes');
    }
};
