<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProformaInvoice;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;

class SmartPaymentRequest
{
    /**
     * Fill the payment request form based on the provided module and ID.
     *
     * @param int|null $id
     * @param string|null $module
     * @param \Filament\Forms\Form $form The form instance to fill.
     */
    public static function fillForm(?int $id, ?string $module, $form, $type = 'balance'): void
    {
        if ($id) {
            if ($module === 'proforma-invoice') {
                self::fillProformaInvoiceForm($id, $form);
            }
            if ($module === 'order') {
                self::fillOrderForm($id, $form, $type);
            }
        }
    }

    /**
     * Fill the form with Proforma Invoice details.
     *
     * @param int $id
     * @param \Filament\Forms\Form $form
     */
    protected static function fillProformaInvoiceForm(int $id, $form): void
    {
        $proformaInvoice = ProformaInvoice::find($id);

        if ($proformaInvoice) {
            $details = Admin::aggregateProformaInvoiceDetails([$proformaInvoice]);

            $form->fill([
                'extra.collectivePayment' => 1,
                'department_id' => 6,
                'type_of_payment' => 'advance',
                'proforma_invoice_numbers' => [$id],
                'beneficiary_name' => 'supplier',
                'supplier_id' => $proformaInvoice->supplier_id ?? null,
                'reason_for_payment' => 20,
                'currency' => 'USD',
                'requested_amount' => $details['requested'] ?? null,
                'total_amount' => $details['total'] ?? null,
                'hidden_proforma_number' => trim($details['number'] ?? ''),
            ]);
        }
    }

    /**
     * Fill the form with Order details.
     *
     * @param int $id
     * @param \Filament\Forms\Form $form
     */
    protected static function fillOrderForm(int $id, $form, $type): void
    {
        $order = Order::find($id);

        if ($order) {
            $orderDetails = optional($order->orderDetail) ?? null;
            $requested = ($orderDetails?->final_total != null && $orderDetails?->final_total != 0.0)
                ? $orderDetails?->final_total
                : $orderDetails?->provisional_total ?? null;
            $total = ($orderDetails?->buying_price ?? 0) * ($orderDetails->buying_quantity ?? 0);

            $form->fill([
                'extra.collectivePayment' => 0,
                'department_id' => 6,
                'type_of_payment' => $type ?? 'balance',
                'proforma_invoice_number' => $order->proforma_number ?? null,
                'part' => 'PR/GR',
                'order_id' => $id,
                'beneficiary_name' => ($type ?? null) === 'balance' ? 'supplier' : ($type === null ? 'supplier' : 'contractor'),
                'supplier_id' => $order->party->supplier_id ?? null,
                'reason_for_payment' => ($type ?? null) === 'balance' ? 20 : ($type === null ? 20 : 23),
                'currency' => ($type ?? null) === 'balance' ? 'USD' : ($type === null ? 'USD' : 'Rial'),
                'requested_amount' => ($type ?? null) === 'balance' ? $requested : 0,
                'total_amount' => ($type ?? null) === 'balance' ? $total : 0,
            ]);
        }
    }
}
