<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;


use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents\Table;
use App\Models\Attachment;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Services\AttachmentDeletionService;
use App\Services\Notification\PaymentRequestService;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        return '💰 ' . $record->currency . ' ' . number_format($record->requested_amount) . '/' . number_format($record->total_amount);
    }


    /**
     * @param Model $record
     * @return void
     */
    public static function send(Model $record): void
    {
        $service = new PaymentRequestService();
        $service->notifyAccountants($record, type: 'delete');
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
            $randomString = Str::random(5);

            // New filename with extension
            $newFileName = "PR-{$timestamp}-{$randomString}-{$name}";

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
            'BL' => $order->doc?->BL_number ?? 'N/A',
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

        $calculatedAmount = (float)$order->orderDetail->final_total > 0
            ? (float)$order->orderDetail->final_total
            : (float)$order->orderDetail->provisional_total;

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
        persistReferenceNumber($replica, 'PR');
        $service = new PaymentRequestService();
        $service->notifyAccountants($replica);
    }

    public static function separateRecordsIntoDeletableAndNonDeletable(Collection $records): void
    {
        $recordsToDelete = $records->filter(fn($record) => $record->payments->isEmpty());
        $recordsNotDeleted = $records->filter(fn($record) => $record->payments->isNotEmpty());

        // Delete the records that have no paymentRequests
        $recordsToDelete->each->delete();
        $recordsToDelete->each(fn(Model $selectedRecord) => Admin::send($selectedRecord));

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

    /**
     * @return TableAction
     */
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

    /**
     * @return TableAction
     */
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

    /**
     * @return TableAction
     */
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

    /**
     * @return TableAction
     */
    public static function rejectRecord(): TableAction
    {
        return TableAction::make('reject')
            ->disabled(fn() => (auth()->user()->role == 'partner' || auth()->user()->role == 'agent'))
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

    /**
     * @param Model $record
     * @return string
     */
    public static function changeBgColor(Model $record): string
    {
        $hasRejectedProforma = $record->associatedProformaInvoices &&
            $record->associatedProformaInvoices->contains(function (ProformaInvoice $proforma) {
                return $proforma->status === 'rejected';
            });

        if ($hasRejectedProforma) {
            return 'bg-cancelled';
        }

        if (!$record || !$record->deadline || !in_array($record->status, ['pending', 'allowed', 'approved'])) {
            return isShadeSelected('payment-request-table');
        }
        $deadline = Carbon::parse($record->deadline);
        $diffInDays = now()->diffInDays($deadline, false);

        if ($diffInDays < 0) {
            return 'bg-past-deadline ';
        }
        return isShadeSelected('payment-request-table');
    }

    public static function calculateTotals($column, $value, $id)
    {
        if (!$id) {
            return '';
        }

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
            'sheba'         => 'sheba_number',
            'card_transfer' => 'card_transfer_number',
            'bank_account'  => 'account_number',
        ];

        if (! empty($state) && $state !== 'cash' && $recipientName && $currency) {
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
}
