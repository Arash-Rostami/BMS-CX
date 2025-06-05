<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Services\PaymentSummarizer;
use Carbon\Carbon;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
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
            ->copyable()
            ->copyMessage('🗐 Ref. No. Copied')
            ->copyMessageDuration(1500)
            ->extraAttributes(['class' => 'copyable-content'])
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
            ->searchable(query: function (QueryBuilder $query, string $search): QueryBuilder {
                return $query->where(function (QueryBuilder $query) use ($search) {
                    $query->where('payment_request', 'like', "%{$search}%");
                });
            })->html()
            ->limit(50)
            ->tooltip(fn(Model $record) => trim(explode("💢", self::getCustomizedDisplayName($record))[1]))
            ->state(fn(Model $record) => self::getCustomizedDisplayName($record));
    }

    /**
     * @return TextColumn
     */
    public static function showPaymentRequestType(): TextColumn
    {
        return TextColumn::make('paymentRequests.type_of_payment')
            ->label('Type')
            ->grow(false)
            ->formatStateUsing(fn($state) => PaymentRequest::$typesOfPayment[$state])
            ->sortable()
            ->searchable()
            ->badge();
    }

    public static function showContractBuyer(): TextColumn
    {
        return TextColumn::make('buyer')
            ->label('Buyer')
            ->grow(false)
            ->state(function ($record) {
                $paymentRequest = optional($record->paymentRequests)->first();
                if ($paymentRequest) {
                    if ($paymentRequest->order_id) {
                        return $paymentRequest->order?->proformaInvoice?->buyer?->name ?? 'N/A';
                    }
                    return $paymentRequest->associatedProformaInvoices()->first()?->buyer?->name ?? 'N/A';
                }
                return 'N/A';
            })
            ->toggleable(isToggledHiddenByDefault: true)
            ->searchable(query: function ( $query, string $search) {
                return $query->where(function ($q) use ($search) {
                    $q->whereHas('paymentRequests.order.proformaInvoice.buyer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })->orWhereHas('paymentRequests.associatedProformaInvoices.buyer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
                });
            });
    }


    /**
     * @return TextColumn
     */
    public static function showPaymentRequestDep(): TextColumn
    {
        return TextColumn::make('paymentRequests.department.code')
            ->label('Department')
            ->tooltip(function (Model $record) {
                $firstRequest = optional($record->paymentRequests)->first();
                return 'Requester: ' . optional($firstRequest)->extra['made_by'] ?? null;
            })
            ->grow(false)
            ->sortable()
            ->searchable(query: function ($query, string $search) {
                return $query->whereHas('paymentRequests', function ($paymentRequestQuery) use ($search) {
                    $paymentRequestQuery->whereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    });
                });
            })
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPaymentRequestCostCenter(): TextColumn
    {
        return TextColumn::make('paymentRequests.costCenter.code')
            ->label('Cost Center')
            ->grow()
            ->toggleable()
            ->sortable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPaymentRequestBeneficiary(): TextColumn
    {
        return TextColumn::make('paymentRequests.beneficiary_name')
            ->label('Beneficiary')
            ->formatStateUsing(function (Model $record) {
                $firstRequest = optional($record->paymentRequests)->first();
                $beneficiaryName = $firstRequest->contractor?->name
                    ?? $firstRequest->supplier?->name
                    ?? $firstRequest->beneficiary?->name
                    ?? null;

                return $beneficiaryName ? (isModernDesign() ? 'Beneficiary: ' . $beneficiaryName : $beneficiaryName) : null;
            })
            ->grow(false)
            ->sortable()
            ->icon('heroicon-o-identification')
            ->badge()
            ->tooltip('Beneficiary')
            ->searchable(query: function (QueryBuilder $query, string $search): QueryBuilder {
                return $query->whereHas('paymentRequests', function ($paymentRequestQuery) use ($search) {
                    PaymentRequest::searchBeneficiaries($paymentRequestQuery, $search);
                });
            });
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
            ->grow(true)
            ->formatStateUsing(fn($state) => $state ? number_format($state, 2) : 'N/A')
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
            ->toggleable(isToggledHiddenByDefault: true)
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
            ->toggleable(isToggledHiddenByDefault: true)
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
            ->toggleable(isToggledHiddenByDefault: true)
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

    public static function showStatus()
    {
        return IconColumn::make('process_status')
            ->label('Status')
            ->grow(false)
            ->alignRight()
            ->getStateUsing(function (Model $record) {
                if ($record->has_processing_payment_request > 0) {
                    return 'processing';
                } elseif ($record->has_rejected_proforma_invoice > 0) {
                    return 'cancelled';
                } else {
                    return 'completed';
                }
            })
            ->tooltip(function (Model $record) {
                if ($record->has_processing_payment_request > 0) {
                    return 'Insufficient Payment';
                } elseif ($record->has_rejected_proforma_invoice > 0) {
                    return 'Refundable Payment';
                } else {
                    return 'Sufficient Payment';
                }
            })
            ->icon(fn(string $state): string => match ($state) {
                'processing' => 'heroicon-o-clock',
                'cancelled' => 'heroicon-o-no-symbol',
                'completed' => 'heroicon-s-check-circle',
                default => 'heroicon-o-question-mark-circle',
            })
            ->color(fn(string $state): string => match ($state) {
                'insufficient' => 'warning',
                'closed' => 'danger',
                'complete' => 'success',
                default => 'gray',
            });
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

    public static function viewPaymentRequester(): TextEntry
    {
        return TextEntry::make('paymentRequests')
            ->label('Requester')
            ->formatStateUsing(function (?Model $record): ?string {
                $madeByValues = optional($record->paymentRequests)
                    ->pluck('extra.made_by')
                    ->filter()
                    ->unique()
                    ->toArray();

                return !empty($madeByValues) ? implode(', ', $madeByValues) : null;
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDepartment(): TextEntry
    {
        return TextEntry::make('paymentRequests')
            ->label('Department')
            ->formatStateUsing(function (?Model $record): ?string {
                $departmentNames = optional($record->paymentRequests)
                    ->pluck('department.name')
                    ->filter()
                    ->unique()
                    ->toArray();

                return !empty($departmentNames) ? implode(', ', $departmentNames) : null;
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewCostCenter(): TextEntry
    {
        return TextEntry::make('paymentRequests')
            ->label('Cost Center')
            ->formatStateUsing(function (?Model $record): ?string {
                $costCenters = optional($record->paymentRequests)
                    ->pluck('costCenter.code')
                    ->filter()
                    ->unique()
                    ->toArray();

                return !empty($costCenters) ? implode(', ', $costCenters) : null;
            })
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
