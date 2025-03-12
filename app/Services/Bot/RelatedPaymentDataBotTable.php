<?php

namespace App\Services\Bot;

use App\Models\PaymentRequest;
use App\Services\BotpressService;

class RelatedPaymentDataBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'pyr_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'order_id', 'type' => 'number', 'modelAttribute' => 'order_id'],
        ['name' => 'proforma_id', 'type' => 'number', 'modelAttribute' => 'proforma_invoice_id'],
    ];


    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('RelatedPaymentDataTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'RelatedPaymentDataTable',
            collect($this->columns)
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }

    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'RelatedPaymentDataTable',
            PaymentRequest::class,
            [
                'id' => 'id',
                'pyr_id' => 'id',
                'order_id' => function ($paymentRequest) {
                    $order = $paymentRequest->order;
                    return $order ? $order->id : null;
                },
                'proforma_id' => function ($paymentRequest) {
                    $proformaInvoice = $paymentRequest->associatedProformaInvoices()->latest()->first();
                    return $proformaInvoice ? $proformaInvoice->id : null;
                },
            ]
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('RelatedPaymentDataTable');
    }
}
