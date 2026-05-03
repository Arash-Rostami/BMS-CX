<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public static function

    typeOptions(): array
    {
        $defaults = ['agreement' => 'Agreement', 'invoice' => 'Invoice', 'person' => 'Person'];

        return static::query()
            ->whereNotNull('type')
            ->distinct()
            ->pluck('type')
            ->reject(fn($v) => isset($defaults[$v]))
            ->mapWithKeys(fn($v) => [$v => ucfirst($v)])
            ->merge($defaults)
            ->sortKeys()
            ->all();
    }
}
