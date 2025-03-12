<?php

namespace App\Services\Bot;

use App\Models\Grade;
use App\Services\BotpressService;

class GradeBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'gr_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'product_id', 'type' => 'number', 'modelAttribute' => 'product_id'],
        ['name' => 'name', 'type' => 'string', 'modelAttribute' => 'name'],
        ['name' => 'description', 'type' => 'string', 'modelAttribute' => 'description'],
    ];

    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('GradeTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'GradeTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'GradeTable',
            Grade::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('GradeTable');
    }
}
