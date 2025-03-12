<?php

namespace App\Services\Bot;

use App\Models\ProformaInvoice;
use App\Services\BotpressService;

class ProformaInvoiceBotTable
{
    private BotpressService $bp;

    protected array $columns = [
        ['name' => 'id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'pi_id', 'type' => 'number', 'modelAttribute' => 'id'],
        ['name' => 'grade_id', 'type' => 'number', 'modelAttribute' => 'grade_id'],
        ['name' => 'quantity', 'type' => 'string', 'modelAttribute' => 'quantity'],
        ['name' => 'price', 'type' => 'string', 'modelAttribute' => 'price'],
        ['name' => 'status', 'type' => 'string', 'modelAttribute' => 'status'],
        ['name' => 'proforma_number', 'type' => 'string', 'modelAttribute' => 'proforma_number'],
        ['name' => 'proforma_date', 'type' => 'string', 'modelAttribute' => 'proforma_date'],
        ['name' => 'contract_number', 'type' => 'string', 'modelAttribute' => 'contract_number'],
        ['name' => 'user_id', 'type' => 'number', 'modelAttribute' => 'user_id'],
        ['name' => 'assignee_id', 'type' => 'number', 'modelAttribute' => 'assignee_id'],
        ['name' => 'category_id', 'type' => 'number', 'modelAttribute' => 'category_id'],
        ['name' => 'product_id', 'type' => 'number', 'modelAttribute' => 'product_id'],
        ['name' => 'buyer_id', 'type' => 'number', 'modelAttribute' => 'buyer_id'],
        ['name' => 'supplier_id', 'type' => 'number', 'modelAttribute' => 'supplier_id'],
        ['name' => 'percentage', 'type' => 'string', 'modelAttribute' => 'percentage'],
        ['name' => 'reference_number', 'type' => 'string', 'modelAttribute' => 'reference_number'],
        ['name' => 'part', 'type' => 'number', 'modelAttribute' => 'part'],
    ];

    public function __construct(BotpressService $bp)
    {
        $this->bp = $bp;
    }

    public function fetch()
    {
        return $this->bp->fetchAllTableRows('ProformaInvoiceTable');
    }

    public function create()
    {
        return $this->bp->createTable(
            'ProformaInvoiceTable',
            collect($this->columns)
                ->reject(fn($colDef) => $colDef['name'] == 'id')
                ->map(fn($colDef) => ['name' => $colDef['name'], 'type' => $colDef['type'] ?? 'string'])
                ->toArray()
        );
    }


    public function insert()
    {
        return $this->bp->insertAllTableRows(
            'ProformaInvoiceTable',
            ProformaInvoice::class,
            collect($this->columns)->mapWithKeys(fn($colDef) => [$colDef['name'] => $colDef['modelAttribute']])
                ->toArray()
        );
    }

    public function delete()
    {
        return $this->bp->deleteAllTableRows('ProformaInvoiceTable');
    }
}
