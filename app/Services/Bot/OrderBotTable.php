<?php

namespace App\Services\Bot;


use App\Models\Order;
use App\Services\BotpressService;

class OrderBotTable
{

    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'o_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'order_number', 'type' => 'string', 'modelAttribute' => 'order_number'],
        ['name' => 'reference_number', 'type' => 'string', 'modelAttribute' => 'reference_number'],
        ['name' => 'invoice_number', 'type' => 'string', 'modelAttribute' => 'invoice_number'],
        ['name' => 'part', 'type' => 'number', 'modelAttribute' => 'part'],
        ['name' => 'grade_id', 'type' => 'number', 'modelAttribute' => 'grade_id'],
        ['name' => 'proforma_number', 'type' => 'string', 'modelAttribute' => 'proforma_number'],
        ['name' => 'proforma_date', 'type' => 'string', 'modelAttribute' => 'proforma_date'],
        ['name' => 'order_status', 'type' => 'string', 'modelAttribute' => 'order_status'],
        ['name' => 'proforma_invoice_id', 'type' => 'number', 'modelAttribute' => 'proforma_invoice_id'],
        ['name' => 'user_id', 'type' => 'number', 'modelAttribute' => 'user_id'],
        ['name' => 'purchase_status_id', 'type' => 'number', 'modelAttribute' => 'purchase_status_id'],
        ['name' => 'category_id', 'type' => 'number', 'modelAttribute' => 'category_id'],
        ['name' => 'product_id', 'type' => 'number', 'modelAttribute' => 'product_id'],
        ['name' => 'order_detail_id', 'type' => 'number', 'modelAttribute' => 'order_detail_id'],
        ['name' => 'party_id', 'type' => 'number', 'modelAttribute' => 'party_id'],
        ['name' => 'logistic_id', 'type' => 'number', 'modelAttribute' => 'logistic_id'],
        ['name' => 'doc_id', 'type' => 'number', 'modelAttribute' => 'doc_id'],
    ];


    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('OrderTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'OrderTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'OrderTable',
            Order::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('OrderTable');
    }
}
