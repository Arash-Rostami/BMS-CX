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
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->enum('type', ['Order', 'ContainerDemurrage', 'CustomsAndPortFees', 'ContainerAcceptance',
                'ShrinkWrap', 'ContainerLashing', 'SgsReport', 'JumboBoxPallet', 'DrumPackaging',
                'Trucking', 'Other'])->default('Order');
            $table->text('purpose')->nullable();
            $table->enum('status', ['pending', 'processing', 'allowed', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->double('individual_amount');
            $table->double('total_amount');
            $table->timestamp('deadline');
            $table->text('description')->nullable();
            $table->enum('beneficiary_name', ['supplier', 'contractor'])->default('supplier');
            $table->string('recipient_name')->nullable();
            $table->text('beneficiary_address')->nullable();
            $table->string('bank_name');
            $table->text('bank_address')->nullable();
            $table->string('account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('IBAN')->nullable();
            $table->enum('currency', ['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial'])->default('USD');
            $table->string('IFSC')->nullable();
            $table->unsignedBigInteger('MICR')->nullable();
            $table->json('extra')->nullable();
            // Foreign keys
            $table->integer('user_id')->unsigned()->index();
            $table->integer('order_id')->unsigned()->index();
            $table->integer('supplier_id')->unsigned()->index()->nullable();
            $table->integer('contractor_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('contractor_id')->references('id')->on('contractors');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
