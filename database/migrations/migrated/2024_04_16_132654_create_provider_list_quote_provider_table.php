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
        Schema::create('provider_list_quote_provider', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->integer('quote_provider_id')->unsigned()->index();
            $table->integer('provider_list_id')->unsigned()->index();
            $table->foreign('quote_provider_id')->references('id')->on('quote_providers')->onDelete('cascade');
            $table->foreign('provider_list_id')->references('id')->on('provider_lists')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_list_quote_provider');
    }
};
