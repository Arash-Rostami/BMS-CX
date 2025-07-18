<?php

namespace App\Models\Traits;

trait PurchaseStatusComputations
{
    public function getEmoticonAttribute()
    {
        return strtok($this->name, ' ') ?: '';
    }

    public function getBareTitleAttribute()
    {
        if (($pos = strpos($this->name, ' ')) === false) {
            return '';
        }

        return substr($this->name, $pos + 1);
    }
}
