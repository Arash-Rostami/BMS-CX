<?php

namespace App\Services;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Models\Order;
use App\Models\ProformaInvoice;
use Filament\Forms\Form;

class SmartPaymentRequest
{
    public static function fillForm(?int $id, ?string $module, Form $form, ?string $type = 'balance'): void
    {
        if (!$id  || ! $module) return;

        match ($module) {
            'proforma-invoice' => self::handleProformaInvoice($id, $form),
            'order' => self::handleOrder($id, $form, $type),
            default => null
        };
    }

    protected static function handleProformaInvoice(int $id, Form $form): void
    {
        if (!$proforma = ProformaInvoice::find($id)) return;

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

    protected static function handleOrder(int $id, Form $form, string $type): void
    {
        if (!$order = Order::with('orderDetail', 'party')->find($id)) return;

        $isBalance = $type === 'balance';
        $detail = $order->orderDetail;

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
