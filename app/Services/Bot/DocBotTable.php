<?php

namespace App\Services\Bot;

use App\Models\Doc;
use App\Services\BotpressService;

class DocBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'doc_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'voyage_number', 'type' => 'string', 'modelAttribute' => 'voyage_number'],
        ['name' => 'declaration_number', 'type' => 'string', 'modelAttribute' => 'declaration_number'],
        ['name' => 'declaration_date', 'type' => 'string', 'modelAttribute' => 'declaration_date'],
        ['name' => 'BL_number', 'type' => 'string', 'modelAttribute' => 'BL_number'],
        ['name' => 'BL_date', 'type' => 'string', 'modelAttribute' => 'BL_date'],
    ];

    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('DocTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'DocTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'DocTable',
            Doc::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('DocTable');
    }
}
