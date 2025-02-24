<?php

namespace App\Listeners;

use App\Events\BalanceApprovedEvent;
use App\Models\Balance;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BalanceApprovedListener
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
    public function handle(BalanceApprovedEvent $event)
    {
        $balanceId = $event->balanceId;
        $balance = Balance::find($balanceId);


        if ($balance && isset($balance->extra['proposed_base'])) {
            $extra = $balance->extra ?? [];

            $proposedBase = $extra['proposed_base'];
            unset($extra['proposed_base']);

            $extra = array_merge($extra, [
                'base_approved_at' => now()->toDateTimeString(),
                'base_approved_by' => auth()->id(),
            ]);

            $balance->update([
                'base' => $proposedBase,
                'extra' => $extra
            ]);

            Notification::make()
                ->title('Credit Approved')
                ->body("Receivable value of " . number_format($proposedBase) . " updated!")
                ->success()
                ->persistent()
                ->send();
        }
    }
}
