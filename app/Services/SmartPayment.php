<?php

namespace App\Services;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentResource\Pages\Admin as PaymentAdmin;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use Filament\Notifications\Notification;

class SmartPayment
{

    /**
     * Fill the payment request form based on the provided module and ID.
     *
     * @param array|null $id
     * @param string|null $module
     * @param \Filament\Forms\Form $form The form instance to fill.
     */
    public static function fillForm(?array $id, ?string $module, $form): void
    {
        if ($id) {
            if ($module === 'proforma-invoice' || $module === 'payment-request') {
                self::fillProformaInvoiceForm($id, $form);
            }

        }
    }

    /**
     * Fill the form with Proforma Invoice details.
     *
     * @param array $id
     * @param \Filament\Forms\Form $form
     */
    protected static function fillProformaInvoiceForm(array $id, $form): void
    {
        $paymentRequests = PaymentRequest::findMany($id);

        if ($paymentRequests->isNotEmpty()) {
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
                'paymentRequests' => $paymentRequests->pluck('id')->toArray(),
                'currency' => $currencies->first(),
                'amount' => $paymentRequests->sum('requested_amount'),
            ]);

            PaymentAdmin::checkAndNotifyForSupplierCredit($paymentRequests);
        }
    }
}
