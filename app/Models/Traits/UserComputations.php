<?php

namespace App\Models\Traits;

use App\Services\AvatarMaker;
use Filament\Panel;
use Illuminate\Support\Facades\Cache;

trait UserComputations
{
    public function canAccessPanel(Panel $panel): bool
    {
        $allowedDomains = ['persolco.com', 'time-gr.com', 'solsuntrading.com', 'admont.ae',
            'persoreco.com', 'zhuoyuanenergy.cn', 'persol.cn', 'qq.com'];
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

    public static function isUserAuthorizedForOrderStatus()
    {
        if (auth()->user()) {
            return in_array(auth()->user()->role, ['manager', 'admin']);
        }
        return false;
    }
}
