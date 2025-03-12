<?php

namespace App\Services;

use Illuminate\Support\Facades\Vite;

class AvatarMaker
{

    /**
     * @return string
     */
    public function createBasePath(): string
    {
        return 'resources/images/avatars/';
    }

    /**
     * @param string $userType
     * @return string
     */
    public function getPNG(string $userType): string
    {
        return Vite::asset(sprintf('%s%s.svg', $this->createBasePath(), $userType == 'partner' ? 'viewer' : $userType));
    }

    /**
     * @param string $role
     * @return string
     */
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
}
