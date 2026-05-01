<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountStatement extends Model
{
    protected $table = 'account_statements';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:3',
        'credit' => 'decimal:3',
        'accrued_interest' => 'decimal:3',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function save(array $options = [])
    {
        return false;
    }

    public function update(array $attributes = [], array $options = [])
    {
        return false;
    }

    public function delete()
    {
        return false;
    }

    public function truncate()
    {
        return false;
    }
}
