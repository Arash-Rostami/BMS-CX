<?php

namespace App\Listeners;

use App\Events\BalanceRejectedEvent;
use App\Models\Balance;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BalanceRejectedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BalanceRejectedEvent $event)
    {
        $balanceId = $event->balanceId;
        $balance = Balance::find($balanceId);
        if ($balance && isset($balance->extra['proposed_base'])) {
            $extra = $balance->extra ?? [];

            unset($extra['proposed_base']);

            $extra = array_merge($extra, [
                'base_rejected_at' => now()->toDateTimeString(),
                'base_rejected_by' => auth()->id(),
            ]);

            $balance->update(['extra' => $extra]);

            Notification::make()
                ->title('Credit Rejected')
                ->body('The credit amount for balance record has NOT been updated.')
                ->persistent()
                ->danger()
                ->send();
        }
    }
}
