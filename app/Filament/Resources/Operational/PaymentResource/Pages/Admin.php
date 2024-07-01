<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Rules\EnglishAlphabet;
use App\Services\NotificationManager;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder;


class Admin
{
    /**
     * @return \Closure
     */
    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name;

            $paymentRequest = $livewire->data['payment_request_id'] ?? 'Unknown-Request';

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

    /**
     * @return Select
     */
    public static function getPaymentRequest(): Select
    {
        return Select::make('payment_request_id')
            ->options(fn($operation) => self::getPaymentRequests($operation))
            ->disabled(fn($operation) => $operation == 'edit')
            ->afterStateUpdated(function ($state, Set $set) {
                static $cachedPaymentRequests = [];

                if (!array_key_exists($state, $cachedPaymentRequests)) {
                    $cachedPaymentRequests[$state] = PaymentRequest::find($state);
                }

                $paymentRequest = $cachedPaymentRequests[$state];

                if ($paymentRequest) {
                    $set('currency', $paymentRequest->currency);
                    $set('amount', $paymentRequest->requested_amount);
                }
            })
            ->live()
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">💳 </span>Payment Request<span class="red"> *</span>'));
    }


    /**
     * @return TextInput
     */
    public static function getAccountNumber(): TextInput
    {
        return TextInput::make('account_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️⃣ </span>Account Number'))
            ->hintColor('primary')
            ->placeholder('Optional: Account number used for transfer');
    }

    /**
     * @return TextInput
     */
    public static function getBankName(): TextInput
    {
        return TextInput::make('bank_name')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🏛️  </span>Bank Name'))
            ->hintColor('primary')
            ->placeholder('Optional: Bank transferring funds');
    }

    /**
     * @return MarkdownEditor
     */
    public static function getNotes(): MarkdownEditor
    {
        return MarkdownEditor::make('extra.note')
            ->label('')
            ->maxLength(65535)
            ->columnSpanFull()
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('Optional')
            ->hint(new HtmlString('<span class="grayscale">ℹ️ </span>Notes'));
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->options(showCurrencies())
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">💱 </span>Currency<span class="red"> *</span>'));
    }

    /**
     * @return TextInput
     */
    public static function getAmount(): TextInput
    {
        return TextInput::make('amount')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">💰 </span>Amount<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->placeholder('The total sum')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getPayer(): TextInput
    {
        return TextInput::make('payer')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">✒️ </span>Payer<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->placeholder('The full name of payer');
    }

    /**
     * @return FileUpload
     */
    public static function getAttachment(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/payment-attachments')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpanFull();
    }

    /**
     * @return TextInput
     */
    public static function getTitleOfAttachment(): TextInput
    {
        return TextInput::make('name')
            ->label('')
            ->placeholder('Type in English ONLY')
            ->hint(new HtmlString('<span class="grayscale">ℹ️️️ </span>Title/Name'))
            ->hintColor('primary')
            ->requiredWith('file_path')
            ->rule(new EnglishAlphabet)
            ->columnSpanFull();
    }


    /**
     * @return TextColumn
     */
    public static function showTimeGap(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Deadline Delta')
            ->formatStateUsing(function (Model $record) {
                $deadline = optional($record->paymentRequests)->deadline;
                return $deadline ? static::calculateTimeGap($record->created_at, $deadline) : null;
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
        return TextColumn::make('paymentRequests.id')
            ->label('Payment Request')
            ->grow(false)
            ->sortable()
            ->badge()
            ->formatStateUsing(fn($state) => self::getCustomizedDisplayName($state))
            ->searchable(query: fn(QueryBuilder $query, string $search) => self::searchReasonInAllocationOrPaymentRequestModels($query, $search));
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
            ->sortable()
            ->badge()
            ->summarize([
                Sum::make()->label('Total'),
                Count::make()->label('Count')
            ]);
    }


    /**
     * @return TextColumn
     */
    public static function showRequestedAmount(): TextColumn
    {
        return TextColumn::make('paymentRequests.requested_amount')
            ->label('Requested Amount')
            ->color('secondary')
            ->grow(false)
            ->formatStateUsing(function (?Model $record) {
                list($currency, $requestedAmount, $totalAmount, $remainingAmount) = self::fetchAmounts($record);

                return $currency . '  ' . $requestedAmount . ' / ' . $totalAmount;
            })
            ->badge()
            ->summarize([
                Sum::make()
                    ->label('Total'),
                Count::make()
                    ->label('Count')
            ]);
    }

    /**
     * @return TextColumn
     */
    public static function showRemainingAmount(): TextColumn
    {
        return TextColumn::make('paymentRequests.total_amount')
            ->label('Remaining Amount')
            ->color('secondary')
            ->grow(false)
            ->formatStateUsing(function (?Model $record) {
                list($currency, $requestedAmount, $totalAmount, $remainingAmount) = self::fetchAmounts($record);

                return $currency . '  ' . $remainingAmount;
            })
            ->badge()
            ->summarize([
//                Summarizer::make()->label('Sum')
//                    ->using(fn(Builder $query) => $query->from('payment_requests')
//                        ->rightJoin('payments', 'payment_requests.id', '=', 'payments.payment_request_id')
//                        ->selectRaw('COALESCE(SUM(payment_requests.total_amount) - SUM(payments.amount), 0) AS total_remaining')
//                        ->whereNull('payment_requests.deleted_at')
//                        ->whereNull('payments.deleted_at')
//                        ->value('total_remaining')),
                Summarizer::make()->label('Total')
                    ->using(fn(Builder $query) => $query
                        ->selectRaw('COALESCE(SUM(total_amount - requested_amount), 0) AS total_remaining')
                        ->whereNull('deleted_at')
                        ->value('total_remaining')),
            ]);
    }

    /**
     * @return TextColumn
     */
    public static function showBankName(): TextColumn
    {
        return TextColumn::make('bank_name')
            ->label('Transferring Bank')
            ->color('secondary')
            ->sortable()
            ->searchable()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showAccountNumber(): TextColumn
    {
        return TextColumn::make('account_number')
            ->label('Transferring Account')
            ->color('secondary')
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
     * @return Grouping
     */
    public static function filterByCurrency(): Grouping
    {
        return Grouping::make('currency')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => data_get($record, 'currency'));
    }

    /**
     * @return Grouping
     */
    public static function filterByPayer(): Grouping
    {
        return Grouping::make('payer')
            ->collapsible();
    }

    /**
     * @return Grouping
     */
    public static function filterByBalance(): Grouping
    {
        return Grouping::make('extra')->collapsible()
            ->label('Balance')
            ->getKeyFromRecordUsing(function (Model $record): string {
                return $record->extra['balanceStatus'] . '-' . $record->payment_request_id;
            })
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->extra['balanceStatus']);
    }

    /**
     * @return Grouping
     */
    public static function filterByPaymentRequest(): Grouping
    {
        return Grouping::make('payment_request_id')->collapsible()
            ->label('Payment Request')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->paymentRequests->reason->reason);
    }


    /**
     * @return TextEntry
     */
    public static function viewOrder(): TextEntry
    {
        return TextEntry::make('order_id')
            ->label('Order')
            ->state(function (Model $record): string {
                return optional($record->order)->invoice_number ?? 'N/A';
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPaymentRequest(): TextEntry
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
        return TextEntry::make('payment_request_id')
            ->label('Payment Type')
            ->formatStateUsing(fn(Model $record) => ucwords($record->paymentRequests->type_of_payment))
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
    public static function viewAccountNumber(): TextEntry
    {
        return TextEntry::make('account_number')
            ->label('Transferring Account')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBankName(): TextEntry
    {
        return TextEntry::make('bank_name')
            ->label('Transferring Bank')
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

    public static function send(Model $record): void
    {

        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $record->paymentRequests->order_invoice_number ?? $record->paymentRequests->reason->reason,
                'type' => 'delete',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payments.index'),
            ]));
        }
    }


    public static function getCustomizedDisplayName($id): string
    {
        $paymentRequest = PaymentRequest::find($id);

        if ($paymentRequest) {
            return $paymentRequest->getCustomizedDisplayName();
        }

        return '';
    }

    protected static function calculateTimeGap($createdAt, $deadline): string
    {
        $daysDifference = Carbon::parse($createdAt)->diffInDays($deadline);
        return $daysDifference === 0 ? 'on the final day' : $daysDifference . ' days';
    }


    private static function calculateDiff(Model $record): float
    {
        $remainderSum = $record->extra['remainderSum'] ?? 0;
        $recordState = $record->paymentRequests->requested_amount;

        return ($record->extra != null)
            ? ($recordState - $record->extra['remainderSum']) - $recordState
            : $record->amount - $recordState;
    }

    /**
     * @param QueryBuilder $query
     * @param string $search
     * @return void
     */
    public static function searchReasonInAllocationOrPaymentRequestModels(QueryBuilder $query, string $search): void
    {
        $query
            ->whereHas('reason', function ($query) use ($search) {
                $query->where('reason', 'like', "%{$search}%");
            })
            ->orWhereHas('paymentRequests', function ($query) use ($search) {
                $query->where('order_invoice_number', 'like', "%{$search}%");
            });
    }

    /**
     * Get payment request options based on operation.
     *
     * @param string $operation
     * @return array
     */
    private static function getPaymentRequests(string $operation): array
    {
        $query = PaymentRequest::orderBy('deadline', 'asc');
        if ($operation == 'create') {
            $query->whereIn('status', ['processing', 'approved', 'allowed']);
        }
        return $query->get()->mapWithKeys(fn($paymentRequest) => [$paymentRequest->id => $paymentRequest->getCustomizedDisplayName()])->toArray();
    }

    /**
     * @param Model|null $record
     * @return array
     */
    public static function fetchAmounts(?Model $record): array
    {
        $currency = $record->paymentRequests->currency ?? '';
        $requestedAmount = number_format($record->paymentRequests->requested_amount ?? 0);
        $totalAmount = number_format($record->paymentRequests->total_amount ?? 0);
        $remainingAmount = number_format($record->paymentRequests->total_amount - $record->amount ?? 0);

        return [$currency, $requestedAmount, $totalAmount, $remainingAmount];
    }
}
