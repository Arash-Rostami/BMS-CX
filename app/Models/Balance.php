<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;



class Balance extends Model
{
    use HasFactory;

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

    public function getRecipientNameAttribute()
    {
        return match ($this->category) {
            'payees' => $this->payee->name ?? 'N/A',
            'beneficiaries' => $this->beneficiary->name ?? 'N/A',
            'suppliers' => $this->supplier->name ?? 'N/A',
            'contractors' => $this->contractor->name ?? 'N/A',
            default => 'Unknown Recipient'
        };
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

    public static function getTabCounts(): array
    {
        $user = auth()->user();
        $userId = $user->id;

        return Cache::remember("balance_tab_counts_{$userId}", 60, function () use ($user) {
            $filteredQuery = self::filterByUserDepartment($user);

            $totalCount = (clone $filteredQuery)->count();

            $departmentCounts = $filteredQuery
                ->with('department:id,code')
                ->selectRaw('department_id, COUNT(*) as record_count')
                ->groupBy('department_id')
                ->get();

            $tabCounts = [];
            foreach ($departmentCounts as $balance) {
                if ($balance->department && $balance->department_id != 0) {
                    $tabCounts[] = [
                        'code' => $balance->department->code,
                        'count' => $balance->record_count,
                        'department_id' => $balance->department_id,
                    ];
                }
            }

            return [
                'total' => $totalCount,
                'departments' => $tabCounts,
            ];
        });
    }

    public static function getGroupedRecipientOptions(): array
    {
        return Cache::remember('recipient_filter_options_' . auth()->id(), 60, function () {
            $balances = self::select('category', 'category_id')
                ->distinct()
                ->with(['supplier:id,name', 'contractor:id,name', 'beneficiary:id,name'])
                ->get();


            $options = [];
            foreach ($balances as $balance) {
                switch ($balance->category) {
                    case 'suppliers':
                        if ($balance->supplier) {
                            $id = $balance->supplier->id;
                            $name = $balance->supplier->name;
                            $options["supplier_{$id}"] = $name;
                        }
                        break;
                    case 'contractors':
                        if ($balance->contractor) {
                            $id = $balance->contractor->id;
                            $name = $balance->contractor->name;
                            $options["contractor_{$id}"] = $name;
                        }
                        break;
                    case 'payees':
                        if ($balance->beneficiary) {
                            $id = $balance->beneficiary->id;
                            $name = $balance->beneficiary->name;
                            $options["payee_{$id}"] = $name;
                        }
                        break;
                }
            }
            return $options;
        });
    }

    public static function isBaseColumnUpdatable($user): bool
    {
        return isUserManager() || isUserAdmin();
//        return isUserManager() || isUserAccountant();
    }

    private static function sendBaseApprovalNotification(Balance $balance)
    {
        $authorizedUsers = User::getUsersByRoles(['admin', 'manager']);
        $proposedBase = $balance->extra['proposed_base'] ?? 0;
        $notificationBody = "The balance update for {$balance->recipient_name}, with a proposed credit of " . number_format($proposedBase, 2) . ", is awaiting your approval.";

        foreach ($authorizedUsers as $user) {
            Notification::make()
                ->title('Balance Credit Approval Required')
                ->body($notificationBody)
                ->actions([
                    Action::make('approve')
                        ->label('Approve')
                        ->button()
                        ->icon('heroicon-o-check-circle')
                        ->iconButton()
                        ->color('success')
                        ->markAsRead()
                        ->close()
                        ->dispatch('BalanceApprovedEvent', [$balance->id])
                        ->button(),
                    Action::make('reject')
                        ->label('Reject')
                        ->button()
                        ->icon('heroicon-o-x-circle')
                        ->iconButton()
                        ->color('danger')
                        ->markAsRead()
                        ->close()
                        ->dispatch('BalanceRejectedEvent', [$balance->id])
                        ->button(),
                ])
                ->sendToDatabase($user);
        }
    }

}

