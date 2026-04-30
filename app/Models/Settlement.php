<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'foreign_settlement_amount' => 'decimal:6',
        'settlement_exchange_rate' => 'decimal:6',
        'settlement_amount' => 'decimal:6',
        'accrued_interest_in_period' => 'decimal:6',
        'deducted_from_interest' => 'decimal:6',
        'deducted_from_principal' => 'decimal:6',
    ];

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
