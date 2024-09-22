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
        Schema::create('logistics', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->date('loading_deadline')->nullable();
            $table->boolean('change_of_destination')->default(false);
            $table->double('number_of_containers')->default(0);
            $table->string('full_container_load_type')->nullable();
            $table->double('ocean_freight')->nullable();
            $table->double('terminal_handling_charges')->nullable();
            $table->string('FCL')->nullable();
            $table->string('booking_number')->nullable();
            $table->double('gross_weight')->default(0)->nullable();
            $table->double('net_weight')->default(0)->nullable();
            $table->unsignedInteger('free_time_POD')->nullable();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('shipping_line_id')->unsigned()->index()->nullable();
            $table->integer('port_of_delivery_id')->unsigned()->index()->nullable();
            $table->integer('delivery_term_id')->unsigned()->index()->nullable();
            $table->integer('packaging_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines');
            $table->foreign('port_of_delivery_id')->references('id')->on('port_of_deliveries');
            $table->foreign('delivery_term_id')->references('id')->on('delivery_terms');
            $table->foreign('packaging_id')->references('id')->on('packagings');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics');
    }
};
