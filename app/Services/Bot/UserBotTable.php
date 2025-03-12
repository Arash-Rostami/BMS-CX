<?php

namespace App\Services\Bot;

use App\Models\User;
use App\Services\BotpressService;

class UserBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'u_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'first_name', 'type' => 'string', 'modelAttribute' => 'first_name'],
        ['name' => 'middle_name', 'type' => 'string', 'modelAttribute' => 'middle_name'],
        ['name' => 'last_name', 'type' => 'string', 'modelAttribute' => 'last_name'],
        ['name' => 'phone', 'type' => 'string', 'modelAttribute' => 'phone'],
        ['name' => 'email', 'type' => 'string', 'modelAttribute' => 'email'],
        ['name' => 'company', 'type' => 'string', 'modelAttribute' => 'company'],
        ['name' => 'role', 'type' => 'string', 'modelAttribute' => 'role'],
        ['name' => 'status', 'type' => 'string', 'modelAttribute' => 'status'],
    ];


    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('UserTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'UserTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }
    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'UserTable',
            User::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('UserTable');
    }
}
