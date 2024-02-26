<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\AvatarMaker;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Vite;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasAvatar;


class User extends Authenticatable implements FilamentUser, HasName, HasAvatar
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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'info' => 'array',
    ];


    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'user_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@time-gr.com');
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

    public function getMiddleNameAttribute()
    {
        return $this->middle_name ?? '';
    }

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

    public function grades()
    {
        return $this->hasMany(Grade::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function orderRequests()
    {
        return $this->hasMany(OrderRequest::class);
    }

    public function packagings()
    {
        return $this->hasMany(Packaging::class, 'user_id');
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class);
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
        return self::role === 'Admin';
    }

    public static function isUserAuthorizedForOrderStatus()
    {
        if (auth()->user()) {
            return in_array(auth()->user()->role, ['manager', 'admin']);
        }
        return false;
    }

}
