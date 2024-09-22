<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents\Table;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\FilamentNotification;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;


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

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            // New filename with extension
            $newFileName = "Payment-{$paymentRequest}-{$timestamp}-{$name}";

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }


    public static function send(Model $record): void
    {
        $records = $record->paymentRequests?->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');

        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $records,
                'type' => 'delete',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payments.index'),
            ]));
        }
    }


    public static function getCustomizedDisplayName($record): string
    {
        if ($record->paymentRequests?->isNotEmpty()) {
            return $record->paymentRequests->map(function ($paymentRequest) {
                return $paymentRequest->getCustomizedDisplayName();
            })->join(', ');
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

    public static function fetchAmounts(?Model $record): array
    {
        if (is_null($record)) {
            return ['currency' => ' ', 'requestedAmount' => 0, 'totalAmount' => 0, 'remainingAmount' => 0];
        }

        $uniqueCurrency = $record->paymentRequests?->pluck('currency')->unique();
        $currency = $uniqueCurrency->count() === 1 ? $uniqueCurrency->first() : ' ';

        $requestedAmount = $record->paymentRequests?->sum('requested_amount') ?? 0;
        $totalAmount = $record->paymentRequests?->sum('total_amount') ?? 0;

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

            foreach ($records as $each) {
                $requestedAmount += (float)$each->requested_amount;
            }

            $cachedPaymentRequests[$stateKey] = $requestedAmount;
        }

        $paymentRequest = $cachedPaymentRequests[$stateKey];

        if ($paymentRequest !== null) {
            $set('currency', 'USD');
            $set('amount', $paymentRequest);
        }
    }
}
