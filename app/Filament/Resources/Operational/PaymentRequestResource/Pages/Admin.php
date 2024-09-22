<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;


use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Table;
use App\Models\Attachment;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentDeletionService;
use App\Services\PaymentRequestService;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class Admin
{

    use Filter, Form, Table;

    protected static array $statusTexts = [
        'pending' => 'New',
        'processing' => 'Processing',
        'allowed' => 'Allowed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];

    protected static array $statusIcons = [
        'pending' => 'heroicon-m-sparkles',
        'processing' => 'heroicon-m-arrow-path',
        'allowed' => 'heroicon-m-check-badge',
        'approved' => 'heroicon-o-clipboard-document-check',
        'rejected' => 'heroicon-m-x-circle',
        'completed' => 'heroicon-o-flag',
        'cancelled' => 'heroicon-s-hand-raised',
    ];

    protected static array $statusColors = [
        'pending' => 'info',
        'processing' => 'warning',
        'allowed' => 'success',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'primary',
        'cancelled' => 'secondary',
    ];


    /**
     * @param Model|null $record
     * @return string
     */
    private static function concatenateSum(?Model $record): string
    {
        return '💰 Sum: ' . $record->currency . ' ' . number_format($record->requested_amount) . '/' . number_format($record->total_amount);
    }


    /**
     * @param Model $record
     * @return void
     */
    public static function send(Model $record): void
    {
        $accountants = (new PaymentRequestService())->fetchAccountants();

        foreach ($accountants as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => self::getOrderRelation($record),
                'type' => 'delete',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.index'),
            ]));
        }
    }


    /**
     * @param Model|null $record
     * @return string
     */
    protected static function showRemainingDays(?Model $record): string
    {
        if (!$record || !$record->deadline) {
            return "No deadline set";
        }

        $daysLeft = Carbon::parse($record->deadline)->diffInDays(Carbon::now());

        return match (true) {
            $daysLeft > 1 => "{$daysLeft} days left",
            $daysLeft === 1 => "1 day left",
            $daysLeft === 0 => "Deadline is today",
            default => "Deadline passed",
        };
    }


    /**
     * @param Model $record
     * @return mixed
     */
    public static function getOrderRelation(Model $record)
    {

        if (!isset($record->proforma_invoice_number)) {
            return PaymentRequest::showAmongAllReasons($record->reason_for_payment);
        }

        if (isset($record->order_id) && $record->order_id != null) {
            return $record->order->invoice_number . ' (' . $record->reference_number . ')';
        }

        return $record->proforma_invoice_number;
    }


    /**
     * @return \Closure
     */
    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record): string {

            $name = $get('name') ?? $file->getClientOriginalName();
            // File extension
            $extension = $file->getClientOriginalExtension();

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            // New filename with extension
            $newFileName = "Payment-Request-{$timestamp}-{$name}";

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }

    protected static function getOrderOptions($get, $set): array
    {
        $proformaNumber = $get('proforma_invoice_number');
        $total = $get('extra.collectivePayment') ?? $set('extra.collectivePayment', 0);
        $part = $get('part');


        if (!$proformaNumber || $total != 0 || empty($part)) {
            return [];
        }


        $relations = match ($part) {
            'BL' => 'doc',
            'BN' => 'logistic',
            'PR/GR' => 'product',
            default => []
        };


        $cacheKey = "order_parts_options:{$proformaNumber}:{$part}";

//        return Cache::remember($cacheKey, 30, function () use ($proformaNumber, $part) {
        $relations = match ($part) {
            'BL' => ['doc'],
            'BN' => ['logistic'],
            'PR/GR' => ['product'],
            default => []
        };

        $orders = Order::with($relations)
            ->whereHas('proformaInvoice', function ($query) use ($proformaNumber) {
                $query->where('proforma_number', $proformaNumber);
            })->get();
        return $orders->mapWithKeys(function ($order) use ($part) {
            $display = static::formatOrderDisplay($order, $part);
            return [$order->id => $display];
        })->toArray();
//        });
    }

    protected static function formatOrderDisplay(Order $order, string $part): string
    {
        return match ($part) {
            'BL' => $order->docs?->BL_number ?? 'N/A',
            'BN' => $order->logistic?->booking_number ? $order->logistic->booking_number . ' (' . ($order->logistic->portOfDelivery?->name ?? 'Unknown Port') . ')' : 'N/A',
            'REF' => $order->reference_number ?? 'N/A',
            'PN' => $order->invoice_number ? $order->invoice_number . ' (Part ' . $order->part . ')' : 'N/A',
            'PR/GR' => $order->product?->name ? $order->product->name . ' (' . $order->grade?->name . ' - Part ' . $order->part . ')' : 'N/A',
            default => 'Unknown Part',
        };
    }

    /**
     * Computes the total and requested amounts for given proforma invoices.
     */
    public static function aggregateProformaInvoiceDetails($all)
    {
        $totalAmount = 0.0;
        $requestedAmount = 0.0;
        $uniqueNumbers = [];


        foreach ($all as $each) {
            $individualTotal = (float)$each->price * (float)$each->quantity;

            $totalAmount += $individualTotal;
            $requestedAmount += ((float)$each->percentage / 100) * $individualTotal;
            $uniqueNumbers[$each->proforma_number] = $each->proforma_number;
        }

        return ['total' => $totalAmount, 'requested' => $requestedAmount, 'number' => implode(', ', $uniqueNumbers)];
    }

    /**
     * @param $state
     * @return array
     */
    protected static function calculateOrderFinancials($state): array
    {
        $order = Order::find($state);

        $price = $order->proformaInvoice->price ?? 0;
        $quantity = $order->proformaInvoice->quantity ?? 0;
        $total = (float)$price * (float)$quantity;

        $currency = $order->orderDetail?->currency ?? 'USD';
        $price = $order->orderDetail->final_price ?? $order->orderDetail->provisional_price ?? $order->orderDetail->buying_price ?? $order->proformaInvoice->price ?? 0;
        $quantity = $order->orderDetail->final_quantity ?? $order->orderDetail->provisional_quantity ?? $order->orderDetail->buying_quantity ?? $order->proformaInvoice->quantity ?? 0;
        $rawAmount = (float)$price * (float)$quantity;

        $calculatedAmount = (float)$order->orderDetail->extra['finalTotal'] > 0
            ? (float)$order->orderDetail->extra['finalTotal']
            : (float)$order->orderDetail->extra['provisionalTotal'];

        $requested = $calculatedAmount != 0 ? $calculatedAmount : $rawAmount;

        return ['total' => $total, 'requested' => $requested, 'currency' => $currency];
    }

//    protected static function showSearchResults($invoice): array
//    {
//        return [$invoice->id => sprintf(
//            "%s (%s - %s) 💢 Ref: %s",
//            $invoice->proforma_number ?? 'N/A',
//            optional($invoice->product)->name ?? 'N/A',
//            optional($invoice->grade)->name ?? 'N/A',
//            $invoice->reference_number ?? 'N/A'
//        )];
//    }

    public static function syncPaymentRequest(Model $replica): void
    {
        $service = new PaymentRequestService();
        $accountants = $service->fetchAccountants();
        $service->persistReferenceNumber($replica);
        $service->notifyAccountants($replica, $accountants);
    }
}
