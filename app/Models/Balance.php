<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = ['base', 'payment', 'total', 'category', 'category_id', 'department_id', 'currency', 'extra', 'user_id'];

    protected $casts = [
        'extra' => 'json',
    ];

    protected static function booted()
    {
        static::creating(function ($balance) {
            $balance->user_id = auth()->id();
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

    // Computational Method
    public function getTotalAttribute()
    {
        return $this->base + $this->payment;
    }

    public function scopeFilterByUserDepartment(Builder $query, $user): Builder
    {
        $departmentId = $user->info['department'] ?? 0;
        $position = $user->info['position'] ?? null;

        if (in_array($user->role, ['admin', 'manager', 'accountant'])) {
            return $query;
        }

        if ($position == 'jnr') {
            return $this->fetchAllUsersPaymentRequest($query, $user);
        }

        return $query->whereIn('department_id', [$departmentId, 0]);
    }

    protected function fetchAllUsersPaymentRequest(Builder $query, $user): Builder
    {
        return $query->whereExists(function ($exists) use ($user) {
            $exists->select(DB::raw(1))
                ->from('payments')
                ->join('payment_payment_request', 'payments.id', '=', 'payment_payment_request.payment_id')
                ->join('payment_requests', 'payment_payment_request.payment_request_id', '=', 'payment_requests.id')
                ->where('payment_requests.user_id', $user->id)
                ->whereNull('payment_requests.deleted_at')
                ->whereNull('payments.deleted_at')
                ->whereColumn('balances.currency', 'payments.currency')
                ->whereColumn('balances.payment', 'payments.amount')
                ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, balances.created_at, payments.created_at)) <= 1');
        });
    }
}
