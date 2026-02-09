<?php

namespace App\Models\Traits;

trait UserInformation
{
    public function hasDepartment(int $department): bool
    {
        return ($this->info['department'] ?? null) == $department;
    }

    public function hasPosition(string $position): bool
    {
        return ($this->info['position'] ?? null) === $position;
    }
}
