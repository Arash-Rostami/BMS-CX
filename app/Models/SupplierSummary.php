<?php

namespace App\Models;

use App\Models\Traits\SupplierSummaryComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SupplierSummary extends Model
{
    use HasFactory, SupplierSummaryComputations;

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
