<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    protected static array $reasonsForPayment = ['Order', 'ContainerDemurrage', 'CustomsAndPortFees',
        'ContainerAcceptance', 'ShrinkWrap', 'ContainerLashing', 'SgsReport', 'JumboBoxPallet', 'DrumPackaging',
        'Trucking', 'Other', 'AdvertisingAndMarketingExpenses', 'CharitableDonationsOrSponsorships',
        'ConsultingFees', 'EmployeesSalariesWagesBenefitsOrPotentialBonuses', 'InsurancePremiums',
        'InterOrganizationalTransfers', 'ITAndSoftwareServices', 'LegalFees', 'MaintenanceAndRepair',
        'PersonalExpenses', 'PortExpenses', 'PurchaseOfNonTradingGoods', 'PurchaseOfTradingGoods',
        'PurchaseOfNonTradingServices', 'PurchaseOfTradingServices', 'RDExpenses',
        'TransportationFees', 'TravelAndAccommodationExpenses'];
    protected static array $typesOfPayment = ['advance', 'partial', 'balance', 'full', 'check', 'credit', 'in_kind',
        'lc', 'cod',];

    protected static array $status = ['pending', 'processing', 'allowed', 'approved', 'rejected', 'completed', 'cancelled'];

    protected static array $departments = ['HR', 'MA', 'AS', 'CM', 'CP', 'AC', 'PS', 'WP', 'MK', 'CH', 'SP', 'CX', 'BD', 'PERSORE', 'SA', 'PO'];
    protected static array $currencies = ['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            // Request Details
            $table->text('order_invoice_number')->nullable();
//            $table->integer('order_id')->nullable();
            $table->integer('part')->nullable();
            $table->text('reason_for_payment')->nullable();
            $table->enum('type_of_payment', self::$typesOfPayment)->nullable()->default('advance');
            $table->enum('departments', self::$departments)->nullable();
            $table->text('purpose')->nullable();
            $table->enum('status', self::$status)->default('pending');
            // Amounts and Deadline
            $table->enum('currency', self::$currencies)->default('USD');
            $table->double('requested_amount'); // Consider renaming to "requested_amount" for clarity
            $table->double('total_amount');
            $table->timestamp('deadline');
            // Description and Beneficiary Details
            $table->text('description')->nullable();
            $table->enum('beneficiary_name', ['supplier', 'contractor'])->default('supplier');
            $table->string('recipient_name')->nullable();
            $table->text('beneficiary_address')->nullable();
            // Payment Information
            $table->string('bank_name');
            $table->text('bank_address')->nullable();
            $table->string('account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('IBAN')->nullable();
            $table->string('IFSC')->nullable(); // Applicable to India
            $table->unsignedBigInteger('MICR')->nullable(); // Applicable to some countries
            // Foreign Keys
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('supplier_id')->unsigned()->index()->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->integer('contractor_id')->unsigned()->index()->nullable();
            $table->foreign('contractor_id')->references('id')->on('contractors');
            $table->integer('payee_id')->unsigned()->index()->nullable();
            $table->foreign('payee_id')->references('id')->on('payees');
            $table->integer('attachment_id')->unsigned()->index()->nullable();
            $table->foreign('attachment_id')->references('id')->on('attachments');
            // Additional Data
            $table->json('extra')->nullable();
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
