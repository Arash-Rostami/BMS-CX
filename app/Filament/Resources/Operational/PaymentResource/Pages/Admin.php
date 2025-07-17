<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents\Table;
use App\Models\PaymentRequest;
use App\Models\SupplierSummary;
use App\Services\Notification\PaymentService;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class Admin
{

    use Form, Table, Filter;

    /**
     * @return \Closure
     */
    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name;
            $paymentRequest = isset($livewire->data['paymentRequests']['id'])
                ? (is_array($livewire->data['paymentRequests']['id'])
                    ? implode("-", $livewire->data['paymentRequests']['id'])
                    : $livewire->data['paymentRequests']['id'])
                : 'Unknown-Request';

            // File extension
            $extension = $file->getClientOriginalExtension();

            // New filename with extension
            $newFileName = sprintf('P-%s-%s-%s-%s', $paymentRequest, now()->format('YmdHis'), Str::random(5), $name);

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }


    public static function send(Model $record): void
    {
        $records = $record->paymentRequests?->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');

        $record['records'] = $records;

        (new PaymentService())->notifyAccountants($record, 'delete');
    }


    public static function getCustomizedDisplayName($record): string
    {
        if ($record->paymentRequests?->isNotEmpty()) {
            return $record->paymentRequests->map(function ($paymentRequest) {
                return $paymentRequest->getCustomizedDisplayName();
            })->join('<br><br>');
        }

        return 'N/A';
    }

    protected static function calculateTimeGap($createdAt, $deadline): string
    {
        $daysDifference = Carbon::parse($createdAt)->diffInDays($deadline);
        return $daysDifference === 0 ? 'on the final day' : $daysDifference . ' days';
    }


    private static function calculateDiff(Model $record): float
    {
        $remainderSum = $record->extra['remainderSum'] ?? 0;
        $recordState = $record->paymentRequests?->sum('requested_amount');

        return ($record->extra != null)
            ? ($recordState - $remainderSum) - $recordState
            : $record->amount - $recordState;
    }


    public static function searchReasonInAllocationOrPaymentRequestModels(QueryBuilder $query, string $search): void
    {
        $query
            ->whereHas('reason', function ($query) use ($search) {
                $query->where('reason', 'like', "%{$search}%");
            })
            ->orWhereHas('paymentRequests', function ($query) use ($search) {
                $query->where('proforma_invoice_number', 'like', "%{$search}%");
            });
    }

    public static function fetchAmounts(?Model $record, $state = null): array
    {
        if (is_null($record)) {
            return ['currency' => ' ', 'requestedAmount' => 0, 'totalAmount' => 0, 'remainingAmount' => 0];
        }

        $uniqueCurrency = $record->paymentRequests?->pluck('currency')->unique();
        $currency = $uniqueCurrency->count() === 1 ? $uniqueCurrency->first() : ' ';


        if ($record->paymentRequests->count() == 1) {
            $requestedAmount = $record->paymentRequests?->sum('requested_amount') ?? 0;
            $totalAmount = $record->paymentRequests?->sum('total_amount') ?? 0;
        } else {
            $requestedAmount = $state;
            $totalAmount = $record->paymentRequests?->sum('total_amount') ?? 0;
        }


        $delta = data_get($record->extra, 'remainderSum', 0);

        $remainder = match (data_get($record->extra, 'balanceStatus')) {
            'debit' => ($totalAmount - $requestedAmount) + $delta,
            'credit' => ($totalAmount - $requestedAmount) - $delta,
            default => ($totalAmount - $requestedAmount),
        };

        $remainingAmount = number_format($remainder ?? 0, 2);

        return [$currency, $requestedAmount, $totalAmount, $remainingAmount];
    }

    public static function updateRequestedAmount($state, Set $set): void
    {
        static $cachedPaymentRequests = [];
        $stateKey = serialize($state);

        if (!array_key_exists($stateKey, $cachedPaymentRequests)) {
            $records = PaymentRequest::findMany($state)->keyBy('id');
            $requestedAmount = 0.0;
            $currencies = [];

            foreach ($records as $each) {
                $requestedAmount += (float)$each->requested_amount;
                $currencies[] = $each->currency;
            }
            $cachedPaymentRequests[$stateKey] = [
                'requestedAmount' => $requestedAmount,
                'currencies' => $currencies,
            ];
        }

        $paymentRequestData = $cachedPaymentRequests[$stateKey];

        if ($paymentRequestData !== null) {
            $uniqueCurrencies = array_unique($paymentRequestData['currencies']);

            $set('amount', $paymentRequestData['requestedAmount']);

            if (count($uniqueCurrencies) === 1) {
                $set('currency', $uniqueCurrencies[0]);
            }
        }

        static::checkAndNotifyForSupplierCredit($records);
    }

    public static function checkAndNotifyForSupplierCredit(Collection $records): void
    {
        $supplierIds = $records->pluck('supplier_id')->filter()->unique();

        if ($supplierIds->count() !== 1) {
            return;
        }

        $supplierId = $supplierIds->first();
        $paymentCurrency = $records->first()->currency;

        $creditAmount = SupplierSummary::query()
            ->where('supplier_id', $supplierId)
            ->where('currency', $paymentCurrency)
            ->sum('diff');

        if ($creditAmount > 0) {
            Notification::make()
                ->title('📢 Supplier Credit Available')
                ->body(new HtmlString("This supplier has a credit of <strong>{$creditAmount} {$paymentCurrency}</strong>. Would you like to apply it?"))
                ->persistent()
                ->actions([
                    NotificationAction::make('apply_credit')
                        ->label('Yes, Apply Credit')
                        ->color('success')
                        ->button()
                        ->dispatch('applyCredit', [$creditAmount])
                        ->close(),
                    NotificationAction::make('decline_credit')
                        ->label('No')
                        ->color('secondary')
                        ->button()
                        ->close(),
                ])->send();
        }
    }
}
