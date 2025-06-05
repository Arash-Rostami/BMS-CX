<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\UserComputations;
use App\Models\Traits\UserRoles;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasAvatar;


class User extends Authenticatable implements FilamentUser, HasName, HasAvatar, CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable,
        SoftDeletes, UserRoles, UserComputations;


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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
//        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'info' => 'array',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'user_id');
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
}
