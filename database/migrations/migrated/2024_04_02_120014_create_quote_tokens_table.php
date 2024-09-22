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
        Schema::create('quote_tokens', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('token')->unique();
            $table->integer('quote_id')->unsigned()->nullable()->index();
            $table->date('validity')->nullable();
            // Foreign keys
            $table->integer('quote_request_id')->unsigned()->index();
            $table->integer('quote_provider_id')->unsigned()->index();
            $table->foreign('quote_request_id')->references('id')->on('quote_requests');
            $table->foreign('quote_provider_id')->references('id')->on('quote_providers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_tokens');
    }
};
