<?php

namespace App\Livewire;

use App\Events\BalanceApprovedEvent;
use App\Events\BalanceRejectedEvent;
use Illuminate\Contracts\View\View;
use Filament\Widgets\Widget;

class NotificationEvents extends Widget
{
    protected $listeners = [
        'BalanceApprovedEvent' => 'handleApproved',
        'BalanceRejectedEvent' => 'handleRejected'
    ];

    public function handleApproved($balanceId)
    {
        event(new BalanceApprovedEvent($balanceId));
    }

    public function handleRejected($balanceId)
    {
        event(new BalanceRejectedEvent($balanceId));
    }

    public static function canView(): bool
    {
        return true;
    }

    public function render(): View
    {
        return view('livewire.notification-events');
    }
}
