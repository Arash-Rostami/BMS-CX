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
        'net_movement' => 'decimal:6',
        'running_balance' => 'decimal:6',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }
}
