<?php

namespace App\Models;

use App\Models\Traits\SupplierSummaryComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;


class SupplierSummary extends Model
{
    use HasFactory;
    use SupplierSummaryComputations;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 43200;
    public $cacheDriver = 'file';
    public $cacheTags = ['supplier_summaries_table'];

    protected $fillable = [
        'proforma_invoice_id',
        'supplier_id',
        'type',
        'contract_number',
        'currency',
        'paid',
        'expected',
        'diff',
        'status',
    ];

    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
