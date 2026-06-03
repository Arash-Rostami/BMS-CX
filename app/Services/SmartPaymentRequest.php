<?php

namespace App\Services;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Models\Order;
use App\Models\ProformaInvoice;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class SmartPaymentRequest
{
    public static function fillForm(mixed $id, ?string $module, Form $form, ?string $type = 'balance'): void
    {
        $id = is_array($id) ? $id[0] ?? null : $id;

        if (!$id || !$module) {
            Notification::make()
                ->title('Invalid Reference')
                ->body('Missing or invalid parameters. Proceeding with an empty form.')
                ->warning()
                ->send();
            return;
        }

        match ($module) {
            'proforma-invoice' => self::handleProformaInvoice($id, $form),
            'order' => self::handleOrder($id, $form, $type),
            default => null
        };
    }

    protected static function handleOrder(int $id, Form $form, ?string $type): void
    {
        $type ??= 'other';
        if (!$order = Order::with('orderDetail', 'party')->find($id)) {
            Notification::make()
                ->title('Order Not Found')
                ->body('The selected order could not be found. Proceeding with an empty form.')
                ->warning()
                ->send();
            return;
        }

        $isBalance = $type === 'balance';
        if (!$detail = $order->orderDetail) {
            Notification::make()
                ->title('Missing Order Details')
                ->body('Order is missing pricing details. Please fix the Order first.')
                ->danger()
                ->send();
            return;
        }

        $requested = self::calculateRequestedAmount($detail);
        $total = $detail->total ?? self::calculateTotal($detail);

        $form->fill([
            'extra.collectivePayment' => 0,
            'department_id' => 6,
            'type_of_payment' => $type,
            'proforma_invoice_number' => $order->proforma_number,
            'part' => 'PR/GR',
            'order_id' => $id,
            'beneficiary_name' => $isBalance ? 'supplier' : 'contractor',
            'supplier_id' => $order->party->supplier_id ?? null,
            'reason_for_payment' => $isBalance ? 20 : 23,
            'currency' => $isBalance ? 'USD' : 'Rial',
            'requested_amount' => $isBalance ? $requested : 0,
            'total_amount' => $isBalance ? $total : 0,
        ]);
    }

    protected static function handleProformaInvoice(int $id, Form $form): void
    {
        if (!$proforma = ProformaInvoice::find($id)) {
            Notification::make()
                ->title('Proforma Invoice Not Found')
                ->body('The selected proforma invoice could not be found. Proceeding with an empty form.')
                ->warning()
                ->send();
            return;
        }

        $details = Admin::aggregateProformaInvoiceDetails([$proforma]);

        $form->fill([
            'extra.collectivePayment' => 1,
            'department_id' => 6,
            'type_of_payment' => 'advance',
            'proforma_invoice_numbers' => [$id],
            'beneficiary_name' => 'supplier',
            'supplier_id' => $proforma->supplier_id,
            'reason_for_payment' => 20,
            'currency' => 'USD',
            'requested_amount' => $details['requested'] ?? null,
            'total_amount' => $details['total'] ?? null,
            'hidden_proforma_number' => trim($details['number'] ?? ''),
        ]);
    }

    private static function calculateRequestedAmount(?object $detail): float
    {
        return match (true) {
            isset($detail->final_total) && $detail->final_total != 0.0 => $detail->final_total,
            default => $detail->provisional_total ?? 0.0
        };
    }

    private static function calculateTotal(?object $detail): float
    {
        $unitPrice = $detail->final_price
            ?? $detail->provisional_price
            ?? $detail->buying_price
            ?? 0.0;

        $quantity = $detail->final_quantity
            ?? $detail->provisional_quantity
            ?? $detail->buying_quantity
            ?? 0;

        return $unitPrice * $quantity;
    }
}
