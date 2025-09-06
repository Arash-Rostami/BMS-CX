<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'description',
        'payment_id',
        'user_id',
    ];

    public static function getBalanceByCurrency(): array
    {
        return self::query()
            ->select('currency', DB::raw("SUM(CASE WHEN `type` = 'credit' THEN amount ELSE -amount END) as balance"))
            ->groupBy('currency')
            ->pluck('balance', 'currency')
            ->map(fn($v) => $v + 0)
            ->toArray();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        $clear = fn() => Cache::forget('cash_transactions');

        static::saved($clear);
        static::deleted($clear);
    }
}
