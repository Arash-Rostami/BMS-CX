<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LedgerEntry extends Model
{

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'amount_foreign_currency' => 'decimal:6',
        'exchange_rate' => 'decimal:6',
        'principal_amount_base' => 'decimal:6',
        'applied_credit_amount' => 'decimal:6',
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($entry) {
            if (empty($entry->rate_matrix_snapshot)) {
                $entry->rate_matrix_snapshot = config('financial.interest_tiers', []);
            }
        });
    }
}
