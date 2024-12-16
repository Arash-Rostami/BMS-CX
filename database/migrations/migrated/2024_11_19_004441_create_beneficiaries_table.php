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
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->enum('payee_type', ['individual', 'legal'])->default('individual');
            $table->string('economic_code')->nullable();
            $table->string('national_id')->nullable();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('vat')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
