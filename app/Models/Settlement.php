<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Settlement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'foreign_settlement_amount' => 'decimal:6',
        'settlement_exchange_rate' => 'decimal:6',
        'settlement_amount' => 'decimal:6',
        'currency_type' => 'string',
        'accrued_interest_in_period' => 'decimal:6',
        'deducted_from_interest' => 'decimal:6',
        'deducted_from_principal' => 'decimal:6',
        'counter_interest_applied' => 'decimal:6',
    ];

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }
}
