<?php

namespace App\Services\Bot;

use App\Models\Logistic;
use App\Services\BotpressService;

class LogisticBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'log_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'loading_deadline', 'type' => 'object', 'modelAttribute' => 'loading_deadline'],
        ['name' => 'change_of_destination', 'type' => 'number', 'modelAttribute' => 'change_of_destination'],
        ['name' => 'number_of_containers', 'type' => 'number', 'modelAttribute' => 'number_of_containers'],
        ['name' => 'full_container_load_type', 'type' => 'string', 'modelAttribute' => 'full_container_load_type'],
        ['name' => 'ocean_freight', 'type' => 'number', 'modelAttribute' => 'ocean_freight'],
        ['name' => 'terminal_handling_charges', 'type' => 'number', 'modelAttribute' => 'terminal_handling_charges'],
        ['name' => 'FCL', 'type' => 'string', 'modelAttribute' => 'FCL'],
        ['name' => 'booking_number', 'type' => 'string', 'modelAttribute' => 'booking_number'],
        ['name' => 'free_time_POD', 'type' => 'number', 'modelAttribute' => 'free_time_POD'],
        ['name' => 'gross_weight', 'type' => 'number', 'modelAttribute' => 'gross_weight'],
        ['name' => 'net_weight', 'type' => 'number', 'modelAttribute' => 'net_weight'],
        ['name' => 'shipping_line_id', 'type' => 'number', 'modelAttribute' => 'shipping_line_id'],
        ['name' => 'port_of_delivery_id', 'type' => 'number', 'modelAttribute' => 'port_of_delivery_id'],
        ['name' => 'delivery_term_id', 'type' => 'number', 'modelAttribute' => 'delivery_term_id'],
        ['name' => 'packaging_id', 'type' => 'number', 'modelAttribute' => 'packaging_id'],
    ];

    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('LogisticTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'LogisticTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'LogisticTable',
            Logistic::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('LogisticTable');
    }
}
