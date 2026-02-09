<?php

namespace App\Models\Traits;

use App\Services\AvatarMaker;
use Filament\Panel;

trait UserComputations
{
    private static array $allowedDomains = [
        'persolco.com', 'time-gr.com', 'solsuntrading.com', 'admont.ae', 'bazorg.com',
        'persoreco.com', 'zhuoyuanenergy.cn', 'persol.cn', 'qq.com'
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array(substr(strrchr($this->email, '@'), 1), self::$allowedDomains);
    }

    public static function getByDepAndPos($department, $position)
    {
        return self::where('info->position', $position)
            ->where('info->department', $department)
            ->where('role', '!=', 'partner')
            ->get();
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
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute()
    {
        $middleName = $this->middle_name ? " {$this->middle_name} " : '';

        return trim("{$this->first_name} {$middleName} {$this->last_name}");
    }

    public static function isUserAuthorizedForOrderStatus(): bool
    {
        if (!$user = auth()->user()) return false;

        return $user->hasRole('admin') || $user->hasRole('manager') || isUserCXHead();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
