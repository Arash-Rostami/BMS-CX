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
        Schema::create('provider_lists', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('name')->nullable();
            $table->boolean('exclude')->default(false);
            $table->text('extra')->nullable();
            // Foreign keys
            $table->integer('quote_provider_id')->unsigned()->index();
            $table->foreign('quote_provider_id')->references('id')->on('quote_providers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_lists');
    }
};
