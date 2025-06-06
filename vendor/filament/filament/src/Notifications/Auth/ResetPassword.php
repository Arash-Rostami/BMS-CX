<?php

namespace Filament\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPassword extends BaseNotification
{

    public string $url;

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }
}
