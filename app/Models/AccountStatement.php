<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountStatement extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'account_statements';
    protected $guarded = ['*'];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:3',
        'credit' => 'decimal:3',
        'accrued_interest' => 'decimal:3',
        'running_balance' => 'decimal:3',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'original_id');
    }
}
