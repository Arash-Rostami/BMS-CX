<?php

namespace App\Services;

use Illuminate\Support\Facades\Vite;

class AvatarMaker
{

    public function get(string $role): string
    {
        return match ($role) {
            'agent' => $this->getPNG('agent'),
            'accountant' => $this->getPNG('accountant'),
            'manager' => $this->getPNG('manager'),
            'admin' => $this->getPNG('admin'),
            default => $this->getPNG('viewer'),
        };
    }

    public function getPNG(string $userType): string
    {
        return Vite::asset(sprintf('%s%s.svg', $this->createBasePath(), $userType == 'partner' ? 'viewer' : $userType));
    }

    public function createBasePath(): string
    {
        return 'resources/images/avatars/';
    }
}
