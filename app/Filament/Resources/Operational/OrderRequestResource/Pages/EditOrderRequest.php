<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\OrderRequestStatusNotification;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification as EmailNotification;


class EditOrderRequest extends EditRecord
{
    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->successNotification(fn(Model $record) => Admin::send($record)),
        ];
    }

    protected function beforeSave()
    {
        session(['old_status' => $this->record->getOriginal('request_status')]);
    }

    protected function afterSave()
    {
        $agents = (new CreateOrderRequest())->fetchAgents();

        $this->sendEditNotification($agents);

        $this->sendStatusNotification($agents);

        $this->clearSessionData();
    }

    protected function sendEditNotification($agents)
    {
        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->product->name,
                'type' => 'edit',
                'module' => 'orderRequest',
                'url' => route('filament.admin.resources.profroma-invoices.edit', ['record' => $this->record->id]),
            ]));
        }
    }

    protected function sendStatusNotification($agents)
    {
        $newStatus = $this->record['request_status'];

        if ($newStatus && $newStatus !== session('old_status')) {

            foreach ($agents as $recipient) {
                $recipient->notify(new FilamentNotification([
                    'record' => $this->record->product->name,
                    'type' => $newStatus === 'review' ? 'processing' : ($newStatus === 'fulfilled' ? 'completed' : $newStatus),
                    'module' => 'order',
                    'url' => route('filament.admin.resources.profroma-invoices.edit', ['record' => $this->record->id]),
                ], true));
            }
        }
    }

    protected function clearSessionData()
    {
        session()->forget('old_status');
    }

    /**
     * @return void
     */
    public function notifyViaEmail($status): void
    {
        $arguments = [
            ($status == 'approved') ? User::getUsersByRole('agent') : User::getUsersByRole('partner'),
            new OrderRequestStatusNotification($this->record, $status)
        ];

        RetryableEmailService::dispatchEmail('order request', ...$arguments);
    }
}
