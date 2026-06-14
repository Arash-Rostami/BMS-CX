<?php

namespace App\Services;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin as PaymentAdmin;
use App\Models\PaymentRequest;
use Filament\Notifications\Notification;

class SmartPayment
{

    public static function fillForm(mixed $ids, ?string $module, $form): void
    {
        $ids = is_array($ids) ? $ids : (is_numeric($ids) ? [$ids] : null);

        if (!$ids && !$module) return;
        if (!$ids || !in_array($module, ['proforma-invoice', 'payment-request'])) {
            Notification::make()
                ->title('Invalid Reference')
                ->body('Missing or invalid parameters. Proceeding with an empty form.')
                ->warning()
                ->send();
            return;
        }

        self::fillProformaInvoiceForm($ids, $form);
    }

    protected static function fillProformaInvoiceForm(array $ids, $form): void
    {
        $paymentRequests = PaymentRequest::findMany($ids);

        if ($paymentRequests->isEmpty()) {
            Notification::make()
                ->title('Records Not Found')
                ->body('The selected records could not be found. Proceeding with an empty form.')
                ->warning()
                ->send();
            return;
        }

        $currencies = $paymentRequests->pluck('currency')->unique();

        if ($currencies->count() > 1) {
            Notification::make()
                ->title('Currency Mismatch')
                ->body('Selected payment requests have different currencies. Please ensure they match before proceeding.')
                ->warning()
                ->send();
            return;
        }

        $totalAmount = $paymentRequests->sum(fn($pr) => $pr->adjustment_amount ?? $pr->requested_amount);


        $form->fill([
            'paymentRequests' => $paymentRequests->modelKeys(),
            'currency' => $currencies->first(),
            'amount' => $totalAmount,
            'calculated_max_amount' => $totalAmount,
            'allowed_currencies' => $currencies->toArray(),
        ]);

        PaymentAdmin::checkAndNotifyForSupplierCredit($paymentRequests);
    }
}
