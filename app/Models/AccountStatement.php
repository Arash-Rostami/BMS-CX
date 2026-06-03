<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountStatement extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'account_statements';
    protected $primaryKey = 'statement_id';
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:6',
        'credit' => 'decimal:6',
        'applied_credit' => 'decimal:6',
        'accrued_interest' => 'decimal:6',
        'counter_interest' => 'decimal:6',
        'net_movement' => 'decimal:6',
        'running_balance' => 'decimal:6',
    ];

    protected $appends = ['net'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function getNetAttribute(): float
    {
        return $this->event_type === 'settlement'
            ? (float)$this->debit - (float)$this->credit - (float)($this->counter_interest ?? 0)
            : (float)$this->debit - (float)$this->credit + (float)($this->accrued_interest ?? 0);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    public function scopeWithNetMovement($query)
    {
        return $query->selectRaw('*, CASE
        WHEN event_type = "settlement"
            THEN debit - credit - COALESCE(counter_interest, 0)
        ELSE
            debit - credit + COALESCE(accrued_interest, 0)
        END AS net_computed'
        );
    }
}
