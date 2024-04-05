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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('origin_port', 255);
            $table->string('destination_port', 255);
            $table->string('container_type', 255)->nullable();
            $table->integer('requires_switch_bl');
            $table->string('commodity', 255)->nullable();
            $table->string('packing', 255)->nullable();
            $table->string('gross_weight', 255)->nullable();
            $table->string('quantity', 255)->nullable();
            $table->string('target_of_rate', 255)->nullable();
            $table->string('target_thc', 255)->nullable();
            $table->string('target_local_charges', 255)->nullable();
            $table->string('target_switch_bl_fee', 255)->nullable();
            $table->date('validity')->nullable();
            $table->text('extra')->nullable();
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
        Schema::dropIfExists('quote_requests');
    }
};
