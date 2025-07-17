<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;


use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Table;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Services\Notification\PaymentRequestService;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    public static function getOrderRelation(Model $record)
    {
        if (!isset($record->proforma_invoice_number)) {
            return PaymentRequest::showAmongAllReasons($record->reason_for_payment);
        }

        if (isset($record->order_id) && $record->order_id != null) {
            return "{$record->order->invoice_number} ({$record->reference_number})";
        }

        return $record->proforma_invoice_number;
    }

    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record): string {

            $name = $get('name') ?? $file->getClientOriginalName();
            $number = $livewire->data['requested_amount'] ?? 'NoRequestedAmt';

            // File extension
            $extension = $file->getClientOriginalExtension();

            // New filename with extension
            $newFileName = sprintf('PR-%s-%s-%s-%s', $number, now()->format('YmdHis'), Str::random(5), $name);

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
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

        return [
            'total' => $totalAmount,
            'requested' => $requestedAmount,
            'number' => implode(', ', $uniqueNumbers)
        ];
    }

    public static function syncPaymentRequest(Model $replica): void
    {
        persistReferenceNumber($replica, 'PR');
        (new PaymentRequestService())->notifyAccountants($replica);
    }

    public static function separateRecordsIntoDeletableAndNonDeletable(Collection $records): void
    {
        if ($records->isEmpty()) return;
        $records->loadMissing('payments');

        [$recordsNotDeleted, $recordsToDelete] = $records->partition(fn($record) => $record->payments->isNotEmpty());

        // Delete the records that have no paymentRequests
        if ($recordsToDelete->isNotEmpty()) {
            $recordsToDelete->each(function (Model $record) {
                $record->delete();
                self::send($record);
            });
        }

        if ($recordsNotDeleted->isNotEmpty()) {
            $recordNames = $recordsNotDeleted->pluck('reference_number')->join(', ');
            Notification::make()
                ->title('Some records were not deleted')
                ->body("The following records could not be deleted because they have payments: $recordNames.")
                ->warning()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Records deleted successfully')
                ->success()
                ->send();
        }
    }

    public static function send(Model $record): void
    {
        (new PaymentRequestService())->notifyAccountants($record, type: 'delete');
    }

    public static function allowRecord(): TableAction
    {
        return TableAction::make('allow')
            ->hidden(fn() => !(auth()->user()->role == 'accountant' || auth()->user()->role == 'admin'))
            ->label('Allow')
            ->color('success')
            ->icon('heroicon-s-check-circle')
            ->tooltip('Awaiting accounting review')
            ->extraAttributes([
                'style' => 'border-top: 2px solid #ddd;',
            ])
            ->visible(fn($record) => $record->status === 'pending')
            ->action(function (Model $record) {
                $record->update(['status' => 'allowed']);
                Notification::make()
                    ->title('Status Updated: Approved')
                    ->success()
                    ->send();
            });
    }

    public static function approveRecord(): TableAction
    {
        return TableAction::make('approve')
            ->hidden(fn() => !(auth()->user()->role == 'manager' || auth()->user()->role == 'admin'))
            ->label('Approve')
            ->color('success')
            ->icon('heroicon-s-check-circle')
            ->tooltip('Managerial approval received')
            ->visible(fn($record) => $record->status === 'pending')
            ->action(function (Model $record) {
                $record->update(['status' => 'approved']);
                Notification::make()
                    ->title('Status Updated: Approved')
                    ->success()
                    ->send();
            });
    }

    public static function processRecord(): TableAction
    {
        return TableAction::make('process')
            ->disabled(fn() => !(auth()->user()->role == 'manager' || auth()->user()->role == 'admin'))
            ->label('Process')
            ->color('warning')
            ->icon('heroicon-s-clock')
            ->tooltip('Payment in progress')
            ->visible(fn($record) => $record->status === 'pending')
            ->action(function (Model $record) {
                $record->update(['status' => 'processing']);
                Notification::make()
                    ->title('Status Updated: In process')
                    ->success()
                    ->send();
            });
    }

    public static function rejectRecord(): TableAction
    {
        return TableAction::make('reject')
//            ->disabled(fn() => (auth()->user()->role == 'partner' || auth()->user()->role == 'agent'))
            ->label('Reject')
            ->color('danger')
            ->icon('heroicon-s-x-circle')
            ->tooltip('Payment request denied')
            ->visible(fn($record) => $record->status === 'pending')
            ->action(function (Model $record) {
                $record->update(['status' => 'rejected']);
                Notification::make()
                    ->title('Status Updated: Rejected')
                    ->success()
                    ->send();
            });
    }

    public static function changeBgColor(Model $record): string
    {
        if ($record->associatedProformaInvoices?->contains('status', 'rejected')) {
            return 'bg-cancelled';
        }

        $shade = isShadeSelected('payment-request-table');
        if (empty($record->deadline) || !in_array($record->status, ['pending', 'allowed', 'approved'], true)) {
            return $shade;
        }

        return now()->isAfter($record->deadline)
            ? 'bg-past-deadline'
            : $shade;
    }

    public static function calculateTotals($column, $value, $id)
    {
        if (!$id) return '';

        return PaymentRequest::where($column, $value)
            ->whereNull('deleted_at')
            ->whereIn('status', ['processing', 'allowed', 'approved', 'completed'])
            ->whereHas('order', fn($query) => $query->where('proforma_invoice_id', $id))
            ->selectRaw('currency, SUM(requested_amount) as total')
            ->groupBy('currency')
            ->get()
            ->map(fn($item) => number_format($item->total) . ' ' . $item->currency)
            ->implode(' | ');
    }

    public static function fetchBankAccountDetails($get, $state, $set): void
    {
        $recipientName = $get('recipient_name');
        $currency = $get('currency');

        $setters = [
            'sheba' => 'sheba_number',
            'card_transfer' => 'card_transfer_number',
            'bank_account' => 'account_number',
        ];

        if (!empty($state) && $state !== 'cash' && $recipientName && $currency) {
            $lastPayment = PaymentRequest::getLastPaymentDetails($recipientName, $state, $currency);

            if ($lastPayment) {
                $set($setters[$state] ?? null, $lastPayment->account_number);
                $set('bank_name', $lastPayment->bank_name);
            } else {
                foreach ($setters as $setter) {
                    $set($setter, null);
                }
                $set('bank_name', null);
            }
        } elseif ($state === 'cash') {
            $set('bank_name', 'None - Cash Transaction');
            foreach ($setters as $setter) {
                $set($setter, null);
            }
        }
    }

    protected static function showRemainingDays(?Model $record): string
    {
        if (empty($record?->deadline)) return 'No deadline set';


        $deadline = Carbon::parse($record->deadline);

        if ($deadline->isToday()) {
            return 'Deadline is today';
        }

        if ($deadline->isFuture()) {
            $days = now()->diffInDays($deadline);
            return $days === 1
                ? '1 day left'
                : "{$days} days left";
        }

        return 'Deadline passed';
    }


    protected static function getOrderOptions($get, $set): array
    {
        $proformaNumber = $get('proforma_invoice_number');
        $total = $get('extra.collectivePayment') ?? $set('extra.collectivePayment', 0);
        $part = $get('part');


        if (!$proformaNumber || $total != 0 || empty($part)) {
            return [];
        }

        $relationMap = [
            'BL' => 'doc',
            'BN' => 'logistic',
            'PR/GR' => 'product',
        ];

        if (!isset($relationMap[$part])) {
            return [];
        }

        return Order::with($relationMap[$part])
            ->whereRelation('proformaInvoice', 'proforma_number', $proformaNumber)
            ->get()
            ->mapWithKeys(fn(Order $order) => [
                $order->id => static::formatOrderDisplay($order, $part)
            ])
            ->toArray();
    }

    protected static function formatOrderDisplay(Order $order, string $part): string
    {
        return match ($part) {
            'BL' => $order->doc?->BL_number ?? 'N/A',
            'BN' => $order->logistic?->booking_number ? $order->logistic->booking_number . ' (' . ($order->logistic->portOfDelivery?->name ?? 'Unknown Port') . ')' : 'N/A',
            'REF' => $order->reference_number ?? 'N/A',
            'PN' => $order->invoice_number ? $order->invoice_number . ' (Part ' . $order->part . ')' : 'N/A',
            'PR/GR' => $order->product?->name ? $order->product->name . ' (' . $order->grade?->name . ' - Part ' . $order->part . ')' : 'N/A',
            default => 'Unknown Part',
        };
    }

    protected static function calculateOrderFinancials($state): array
    {
        $order = Order::with('proformaInvoice', 'orderDetail')
            ->find($state);

        $invoice = $order->proformaInvoice;
        $detail = $order->orderDetail;

        $basePrice = (float)($invoice->price ?? 0.0);
        $baseQuantity = (float)($invoice->quantity ?? 0.0);
        $total = $basePrice * $baseQuantity;

        $currency = $detail->currency ?: 'USD';

        $price = (float)($detail->final_price ??
            $detail->provisional_price ??
            $detail->buying_price ??
            $invoice->price);

        $quantity = (float)($detail->final_quantity ??
            $detail->provisional_quantity ??
            $detail->buying_quantity ??
            $invoice->quantity);

        $rawAmount = $price * $quantity;

        $calculated = (float)$detail->final_total > 0
            ? (float)$detail->final_total
            : (float)$detail->provisional_total;
        $requested = $calculated != 0 ? $calculated : $rawAmount;

        return ['total' => $total, 'requested' => $requested, 'currency' => $currency];
    }

    private static function concatenateSum(?Model $record): string
    {
        if (empty($record)) return '';


        $currency = $record->currency ?: 'USD';
        $requested = number_format($record->requested_amount ?? 0);
        $total = number_format($record->total_amount ?? 0);

        return sprintf('💰 %s %s/%s', $currency, $requested, $total);
    }
}
