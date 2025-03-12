<?php

namespace App\Services\Bot;

use App\Models\DeliveryTerm;
use App\Services\BotpressService;

class DeliveryTermBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'dt_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'name', 'type' => 'string', 'modelAttribute' => 'name'],
        ['name' => 'description', 'type' => 'string', 'modelAttribute' => 'description'],
    ];

    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('DeliveryTermTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'DeliveryTermTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'DeliveryTermTable',
            DeliveryTerm::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('DeliveryTermTable');
    }
}
