<?php

namespace App\Models\Traits;

use Illuminate\Support\HtmlString;

trait ChatComputations
{
    public function getChatWriter()
    {
        $createdAt = $this->created_at->diffForHumans();
        $log = " {$this->user->fullName}, {$createdAt}";

        return new HtmlString('<span class="grayscale">💬</span> <span class="italic">' . $log . '</span>');
    }
}
