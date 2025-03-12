<?php

namespace App\Services\Bot;

use App\Models\Product;
use App\Services\BotpressService;

class ProductBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'pr_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'name', 'type' => 'string', 'modelAttribute' => 'name'],
        ['name' => 'description', 'type' => 'string', 'modelAttribute' => 'description'],
        ['name' => 'category_id', 'type' => 'number', 'modelAttribute' => 'category_id'],
    ];

    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('ProductTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'ProductTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'ProductTable',
            Product::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('ProductTable');
    }
}
