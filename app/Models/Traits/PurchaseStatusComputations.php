<?php

namespace App\Models\Traits;

trait PurchaseStatusComputations
{
    public function getEmoticonAttribute()
    {
        return explode(' ', $this->name)[0] ?? '';
    }

    public function getBareTitleAttribute()
    {
        $parts = explode(' ', $this->name);
        array_shift($parts);
        return implode(' ', $parts);
    }
}
