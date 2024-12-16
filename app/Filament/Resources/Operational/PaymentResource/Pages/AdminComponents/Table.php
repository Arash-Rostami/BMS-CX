<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents;

use App\Models\PaymentRequest;
use App\Services\PaymentSummarizer;
use Carbon\Carbon;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\HtmlString;

trait Table
{

    public static function showID(): TextColumn
    {
        return TextColumn::make('reference_number')
            ->label(new HtmlString('<span class="text-primary-500 cursor-pointer" title="Record Unique ID">Ref. No. ⋮ ID</span>'))
            ->weight(FontWeight::ExtraLight)
            ->sortable()
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => ($state) ? "Payment Ref. No. / ID" : '')
            ->toggleable()
            ->searchable();
    }

    public static function showPaymentRequestID(): TextColumn
    {
        return TextColumn::make('paymentRequests.reference_number')
            ->label('Pay. Req. Ref. No.')
            ->weight(FontWeight::ExtraLight)
            ->sortable()
            ->grow(false)
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeGap(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Deadline Delta')
            ->formatStateUsing(function (Model $record) {

                if ($record->paymentRequests) {
                    $deadlines = $record->paymentRequests->pluck('deadline')->filter();

                    if ($deadlines->isNotEmpty()) {
                        $nearestDeadline = $deadlines->min();
                        return static::calculateTimeGap($record->created_at, $nearestDeadline);
                    }
                    return 'Undefined';
                }
                return 'Undefined';
            })
            ->grow(false)
            ->color('info')
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showPaymentRequest(): TextColumn
    {
        return TextColumn::make('paymentRequests.proforma_invoice_number')
            ->label('Payment Request')
            ->grow(false)
            ->sortable()
            ->badge()
            ->summarize(
                Summarizer::make()
                    ->label('Total Payments')
                    ->using(fn(Builder $query): int => PaymentSummarizer::calculateTotalPaymentCount($query))
            )
            ->searchable()
            ->html()
            ->state(fn(Model $record) => self::getCustomizedDisplayName($record));
    }

    /**
     * @return TextColumn
     */
    public static function showPaymentRequestType(): TextColumn
    {
        return TextColumn::make('paymentRequests.type_of_payment')
            ->label('Type')
            ->grow()
            ->formatStateUsing(fn($state) => PaymentRequest::$typesOfPayment[$state])
            ->sortable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showTransferredAmount(): TextColumn
    {
        return TextColumn::make('amount')
            ->label('Paid Amount')
            ->color('warning')
            ->grow(false)
            ->state(fn(?Model $record) => "💰 Sum: {$record->currency} " . number_format($record->amount) . " transferred by {$record->payer}")
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Creation Time')
            ->icon('heroicon-s-calendar-days')
            ->dateTime()
            ->sortable()
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return TextColumn
     */
    public static function showPayer(): TextColumn
    {
        return TextColumn::make('payer')
            ->color('secondary')
            ->sortable()
            ->searchable()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showAmount(): TextColumn
    {
        return TextColumn::make('amount')
            ->label('Payable Amount')
            ->color('info')
            ->grow(false)
            ->state(fn(?Model $record) => '💰 ' . $record->currency . ' ' . number_format($record->amount))
            ->searchable()
            ->sortable()
            ->badge()
            ->summarize(
                Summarizer::make()
                    ->label('Total')
                    ->using(fn(Builder $query) => PaymentSummarizer::calculateTotalsByCurrency($query))
            );
//            ->summarize([
//                Sum::make()->label('Total'),
//            ]);
    }


    /**
     * @return TextColumn
     */
    public static function showCurrency(): TextColumn
    {
        return TextColumn::make('paymentRequests.currency')
            ->label('💱')
            ->color('secondary')
            ->grow(false)
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showRequestedAmount(): TextColumn
    {
        return TextColumn::make('paymentRequests.requested_amount')
            ->label('Requested')
            ->color('secondary')
            ->formatStateUsing(fn($state) => $state ? number_format($state, 2) : 'N/A')
            ->grow(false)
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showTotalAmount(): TextColumn
    {
        return TextColumn::make('total_amount_display')
            ->label('Total')
            ->color('secondary')
            ->getStateUsing(function ($record) {
                $paymentRequests = $record->paymentRequests;

                if ($paymentRequests->isEmpty()) {
                    return 'N/A';
                }

                $total = $paymentRequests->sum('total_amount');
                $requested = $paymentRequests->sum('requested_amount');
                $remaining = $total - $requested;

                $totalFormatted = number_format($total, 2);
                $remainingFormatted = number_format($remaining, 2);

                return "{$totalFormatted} ┆ Remaining: {$remainingFormatted}";
            })
            ->grow(false)
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showTransactionID(): TextColumn
    {
        return TextColumn::make('transaction_id')
            ->label('Transaction ID')
            ->color('secondary')
            ->sortable()
            ->searchable()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showCreator(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->label('Created by')
            ->badge()
            ->color('secondary')
            ->searchable(['first_name', 'last_name'])
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showDate(): TextColumn
    {
        return TextColumn::make('date')
            ->label('Transferring Date')
            ->color('secondary')
            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('F j, Y') : null)
            ->sortable()
            ->searchable()
            ->toggleable()
            ->badge();
    }


    public static function showBalance(): TextColumn
    {
        return TextColumn::make('extra.remainderSum')
            ->label('Amount Delta')
            ->formatStateUsing(function (Model $record) {
                $diff = self::calculateDiff($record);
                return match (true) {
                    ($diff > 0) => '🔺 Overpayment: +' . number_format($diff, 0),
                    ($diff < 0) => '🔻 Underpayment: -' . number_format(abs($diff), 0),
                    default => '⚖️ Balance',
                };
            })
            ->grow(false)
            ->color(function (Model $record) {
                $diff = self::calculateDiff($record);
                return match (true) {
                    ($diff > 0) => 'danger',
                    ($diff < 0) => 'warning',
                    default => 'info',
                };
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewOrder(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label('Order')
            ->state(function (Model $record): string {
                return $record->paymentRequests->map(
                    function ($paymentRequest) {
                        if ($paymentRequest->order_id) {
                            return $paymentRequest->order->invoice_number;
                        }
                        return "Unrelated to orders, but related to PI No. {$paymentRequest->proforma_invoice_number}";
                    }
                )->join(', ') ?? 'N/A';
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPaymentRequest(): TextEntry
    {
        return TextEntry::make('paymentRequests.reference_number')
            ->label('Payment request')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPaymentRequestReason(): TextEntry
    {
        return TextEntry::make('paymentRequests.reason.reason')
            ->label('Payment Purpose')
//            ->formatStateUsing(fn(Model $record) => ucwords($record->paymentRequests->type_of_payment))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPaymentType(): TextEntry
    {
        return TextEntry::make('payment_request')
            ->label('Payment Type')
            ->formatStateUsing(fn(Model $record) => ucwords($record->paymentRequests->map(fn($pr) => $pr->type_of_payment)->join(', ')))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPaymentRequestDetail(): TextEntry
    {
        return TextEntry::make('paymentRequests.total_amount')
            ->label('Requested | Total | Remaining sums')
            ->formatStateUsing(function (Model $record) {
                list($currency, $requestedAmount, $totalAmount, $remainingAmount) = self::fetchAmounts($record);

                return sprintf('%s %s | %s | %s', $currency, $requestedAmount, $totalAmount, $remainingAmount);
            })
            ->color('info')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPayer(): TextEntry
    {
        return TextEntry::make('payer')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewTransferredAmount(): TextEntry
    {
        return TextEntry::make('amount')
            ->state(fn(?Model $record) => '💰 Sum: ' . $record->currency . ' ' . number_format($record->amount))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewTransactionID(): TextEntry
    {
        return TextEntry::make('transaction_id')
            ->label('Transaction ID')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDate(): TextEntry
    {
        return TextEntry::make('date')
            ->date()
            ->label('Transferring Date')
            ->badge();
    }

    /**
     * @return ImageEntry
     */
    public static function viewAttachments(): ImageEntry
    {
        return ImageEntry::make('file_path')
            ->label('')
            ->extraAttributes(fn($state) => $state ? [
                'class' => 'cursor-pointer',
                'title' => '👁️‍',
                'onclick' => "showImage('" . url($state) . "')",
            ] : [])
            ->disk('filament')
            ->alignCenter()
            ->visibility('public');
    }
}
