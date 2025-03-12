<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\AvatarMaker;
use Filament\Panel;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasAvatar;


class User extends Authenticatable implements FilamentUser, HasName, HasAvatar, CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'email',
        'company',
        'role',
        'status',
        'password',
        'info',
        'image',
        'ip_address',
        'last_login',
        'theme',
        'theme_color'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
//        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'info' => 'array',
    ];


    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'user_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $allowedDomains = ['persolco.com', 'time-gr.com', 'solsuntrading.com', 'persoreco.com'];
        return in_array(substr(strrchr($this->email, '@'), 1), $allowedDomains);
    }

    public function getExtraValueAttribute($key)
    {
        return data_get($this->extra, $key);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return (new AvatarMaker())->get($this->role);
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullNameAttribute()
    {
        $middleName = $this->middle_name ? " {$this->middle_name} " : '';
        return "{$this->first_name} {$middleName} {$this->last_name}";
    }

//    public function getMiddleNameAttribute()
//    {
//        return $this->middle_name ?? '';
//    }

    public function buyers()
    {
        return $this->hasMany(Buyer::class, 'user_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'user_id');
    }

    public function deliveryTerms()
    {
        return $this->hasMany(DeliveryTerm::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'info->department');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'user_id');
    }

    public function notificationSubscriptions()
    {
        return $this->hasMany(NotificationSubscription::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function packagings()
    {
        return $this->hasMany(Packaging::class, 'user_id');
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function portOfDeliveries()
    {
        return $this->hasMany(PortOfDelivery::class, 'user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function purchaseStatuses()
    {
        return $this->hasMany(PurchaseStatus::class, 'user_id');
    }


    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'user_id');
    }

    public static function isAdmin()
    {
        $user = auth()->user();
        return $user && $user->role === 'admin';
    }

    public static function isManager()
    {
        $user = auth()->user();
        return $user && $user->role === 'manager';
    }

    public static function isUserAuthorizedForOrderStatus()
    {
        if (auth()->user()) {
            return in_array(auth()->user()->role, ['manager', 'admin']);
        }
        return false;
    }

    public static function getManager()
    {
        $cacheKey = 'users_with_role_manager_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'manager')
                ->orWhere('role', 'admin')
                ->get();
        });
    }

    public static function getAccountant()
    {
        $cacheKey = 'users_with_role_accountant_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'accountant')
                ->orWhere('role', 'admin')
                ->get();
        });
    }

    public static function getAgent()
    {
        $cacheKey = 'users_with_role_agent_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'agent')
                ->orWhere('role', 'admin')
                ->get();
        });
    }

    public static function getPartner()
    {
        $cacheKey = 'users_with_role_partner_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'partner')
                ->orWhere('role', 'admin')
                ->get();
        });
    }

    public function hasRole(string $role): bool
    {
        return strtolower($this->role) == strtolower($role);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByRoles($query, $roles)
    {
        return $query->whereIn('role', $roles);
    }

    public function scopeExcludeRole($query, $role)
    {
        return $query->where('role', '!=', $role);
    }

    public function scopeExcludeRoles($query, $roles)
    {
        return $query->whereNotIn('role', $roles);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function getByDepAndPos($department, $position)
    {
        return self::where('info->position', $position)
            ->where('info->department', $department)
            ->where('role', '!=', 'partner')
            ->get();
    }

    public static function getUsersByRole($role)
    {
        return self::active()->byRole($role)->get();
    }

    public static function getUsersByRoles($roles)
    {
        return self::active()->byRoles($roles)->get();
    }

    public static function getUsersExcludingRole($role)
    {
        return self::active()->excludeRole($role)->get();
    }

    public static function getUsersExcludingRoles($roles)
    {
        return self::active()->excludeRoles($roles)->get();
    }
}
