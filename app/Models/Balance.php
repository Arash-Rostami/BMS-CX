<?php

namespace App\Models;


use App\Models\Traits\BalanceComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class Balance extends Model
{
    use HasFactory, BalanceComputations;

    protected $fillable =
        [
            'base',
            'payment',
            'total',
            'category',
            'category_id',
            'department_id',
            'currency',
            'extra',
            'user_id',
            'created_at',
            'updated_at'
        ];

    protected $casts = [
        'extra' => 'json',
    ];

    protected static function booted()
    {
        static::creating(function ($balance) {
            $balance->user_id = auth()->id();
            if (!self::isBaseColumnUpdatable(Auth::user()) && !is_null($balance->base) && $balance->base != 0) {
                $balance->extra = array_merge(
                    $balance->extra ?? [],
                    ['proposed_base' => $balance->base]
                );
                $balance->base = 0;
            }
        });

        static::updating(function ($balance) {
            if (!self::isBaseColumnUpdatable(Auth::user()) && $balance->isDirty('base')) {
                $originalBase = $balance->getOriginal('base');
                $balance->extra = array_merge(
                    $balance->extra ?? [],
                    ['proposed_base' => $balance->base]
                );
                $balance->base = $originalBase;
            }
        });

        static::created(function ($balance) {
            if (isset($balance->extra['proposed_base'])) {
                self::sendBaseApprovalNotification($balance);
            }
        });

        static::updated(function ($balance) {
            if (isset($balance->extra['proposed_base']) && $balance->wasChanged('extra')) {
                self::sendBaseApprovalNotification($balance);
            }
        });
    }


    public function contractor()
    {
        return $this->belongsTo(Contractor::class, 'category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class, 'category_id');
    }

    public function payee()
    {
        return $this->belongsTo(Beneficiary::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

