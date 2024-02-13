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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->string('first_name', 191);
            $table->string('middle_name', 191)->nullable();
            $table->string('last_name', 191);
            $table->string('phone')->nullable();
            $table->string('email', 191)->unique();
            $table->string('company', 191)->nullable();
            $table->enum('role', ['Agent', 'Accountant', 'Manager', 'Partner', 'Admin'])->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->softDeletes();
            $table->timestamp('last_login')->nullable();
            $table->string('password');
            $table->json('info')->nullable();
            $table->string('image')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->string('theme', 255)->default('default');
            $table->string('theme_color', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
