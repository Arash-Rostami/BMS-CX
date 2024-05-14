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
        Schema::create('allocations', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->text('reason');
            $table->enum('department', ['all', 'HR', 'MA', 'AS', 'CM', 'CP', 'AC', 'PS', 'WP', 'MK', 'CH', 'SP', 'CX', 'BD', 'PERSORE', 'SA', 'PO']);
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
