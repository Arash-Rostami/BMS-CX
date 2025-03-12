<?php

namespace App\Services\Bot;

use App\Models\PaymentRequest;
use App\Services\BotpressService;

class PaymentRequestBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'pyr_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'reference_number', 'type' => 'string', 'modelAttribute' => 'reference_number'],
        ['name' => 'reason_for_payment', 'type' => 'string', 'modelAttribute' => 'reason_for_payment'],
        ['name' => 'type_of_payment', 'type' => 'string', 'modelAttribute' => 'type_of_payment'],
        ['name' => 'cost_center', 'type' => 'number', 'modelAttribute' => 'cost_center'],
        ['name' => 'status', 'type' => 'string', 'modelAttribute' => 'status'],
        ['name' => 'currency', 'type' => 'string', 'modelAttribute' => 'currency'],
        ['name' => 'requested_amount', 'type' => 'number', 'modelAttribute' => 'requested_amount'],
        ['name' => 'total_amount', 'type' => 'number', 'modelAttribute' => 'total_amount'],
        ['name' => 'deadline', 'type' => 'string', 'modelAttribute' => 'deadline'],
        ['name' => 'beneficiary_name', 'type' => 'string', 'modelAttribute' => 'beneficiary_name'],
        ['name' => 'recipient_name', 'type' => 'string', 'modelAttribute' => 'recipient_name'],
        ['name' => 'beneficiary_address', 'type' => 'string', 'modelAttribute' => 'beneficiary_address'],
        ['name' => 'bank_name', 'type' => 'string', 'modelAttribute' => 'bank_name'],
        ['name' => 'account_number', 'type' => 'string', 'modelAttribute' => 'account_number'],
        ['name' => 'part', 'type' => 'string', 'modelAttribute' => 'part'],
        ['name' => 'order_id', 'type' => 'number', 'modelAttribute' => 'order_id'],
        ['name' => 'supplier_id', 'type' => 'number', 'modelAttribute' => 'supplier_id'],
        ['name' => 'contractor_id', 'type' => 'number', 'modelAttribute' => 'contractor_id'],
        ['name' => 'date', 'type' => 'string', 'modelAttribute' => 'date'],
    ];


    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('PaymentRequestTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'PaymentRequestTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        $columnMap = collect($this->columns)->mapWithKeys(function ($colDef) {
            return [$colDef['name'] => $colDef['modelAttribute']];
        })->toArray();

        $columnMap['date'] = function ($paymentRequest) {
            $payments = $paymentRequest->payments()->latest()->first();
            return $payments ? $payments->date : null;
        };


        return $this->bp->insertAllTableRows(
            'PaymentRequestTable',
            PaymentRequest::class,
            $columnMap
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('PaymentRequestTable');
    }
}
