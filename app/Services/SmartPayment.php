<?php

namespace App\Services;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin as PaymentAdmin;
use App\Models\PaymentRequest;
use Filament\Notifications\Notification;

class SmartPayment
{

    public static function fillForm(?array $ids, ?string $module, $form): void
    {
        if (!$ids || !in_array($module, ['proforma-invoice', 'payment-request'])) {
            return;
        }

        self::fillProformaInvoiceForm($ids, $form);
    }

    protected static function fillProformaInvoiceForm(array $ids, $form): void
    {
        $paymentRequests = PaymentRequest::findMany($ids);

        if ($paymentRequests->isEmpty()) return;

        $currencies = $paymentRequests->pluck('currency')->unique();

        if ($currencies->count() > 1) {
            Notification::make()
                ->title('Currency Mismatch')
                ->body('Selected payment requests have different currencies. Please ensure they match before proceeding.')
                ->warning()
                ->send();
            return;
        }

        $form->fill([
            'paymentRequests' => $paymentRequests->modelKeys(),
            'currency' => $currencies->first(),
            'amount' => $paymentRequests->sum('requested_amount'),
        ]);

        PaymentAdmin::checkAndNotifyForSupplierCredit($paymentRequests);
    }
}
