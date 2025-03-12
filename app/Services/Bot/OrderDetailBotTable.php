<?php

namespace App\Services\Bot;

use App\Models\OrderDetail;
use App\Services\BotpressService;

class OrderDetailBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'od_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'provisional_quantity', 'number' => 'number', 'modelAttribute' => 'provisional_quantity'],
        ['name' => 'final_quantity', 'type' => 'number', 'modelAttribute' => 'final_quantity'],
        ['name' => 'provisional_price', 'type' => 'number', 'modelAttribute' => 'provisional_price'],
        ['name' => 'final_price', 'type' => 'number', 'modelAttribute' => 'final_price'],
        ['name' => 'currency', 'type' => 'string', 'modelAttribute' => 'currency'],
        ['name' => 'remaining', 'type' => 'number', 'modelAttribute' => 'remaining'],
        ['name' => 'payment', 'type' => 'number', 'modelAttribute' => 'payment'],
        ['name' => 'initial_payment', 'number' => 'number', 'modelAttribute' => 'initial_payment'],
        ['name' => 'provisional_payment', 'number' => 'number', 'modelAttribute' => 'provisional_payment'],
        ['name' => 'total', 'type' => 'number', 'modelAttribute' => 'total'],
        ['name' => 'initial_total', 'type' => 'number', 'modelAttribute' => 'initial_total'],
        ['name' => 'provisional_total', 'type' => 'number', 'modelAttribute' => 'provisional_total'],
        ['name' => 'final_total', 'type' => 'number', 'modelAttribute' => 'final_total'],
    ];


    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('OrderDetailTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'OrderDetailTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'OrderDetailTable',
            OrderDetail::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('OrderDetailTable');
    }
}
