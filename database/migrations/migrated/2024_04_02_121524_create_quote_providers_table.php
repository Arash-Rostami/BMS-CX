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
        Schema::create('quote_providers', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('title');
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->text('extra')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_providers');
    }
};
