<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'amount_foreign_currency' => 'decimal:6',
        'exchange_rate' => 'decimal:6',
        'principal_amount_base' => 'decimal:6',
        'commission_amount' => 'decimal:6',
        'total_disbursed_base' => 'decimal:6',
        'rate_matrix_snapshot' => 'array',
        'remaining_principal' => 'decimal:6',
        'unpaid_interest' => 'decimal:6',
        'is_settled' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }
}
