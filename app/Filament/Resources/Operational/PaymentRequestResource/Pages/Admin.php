<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Policies\PaymentRequestPolicy;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Filament\Tables\Grouping\Group as Grouping;


class Admin
{
    protected static array $statusTexts = [
        'new' => 'New',
        'processing' => 'Processing',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];

    protected static array $statusIcons = [
        'new' => 'heroicon-m-sparkles',
        'processing' => 'heroicon-m-arrow-path',
        'approved' => 'heroicon-m-check-badge',
        'rejected' => 'heroicon-m-x-circle',
        'completed' => 'heroicon-o-flag',
        'cancelled' => 'heroicon-s-hand-raised',
    ];

    protected static array $statusColors = [
        'new' => 'info',
        'processing' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'primary',
        'cancelled' => 'secondary',
    ];


    /**
     * @return Radio
     */
    public static function getStatus(): Radio
    {
        return Radio::make('status')
            ->options(PaymentRequest::$status)
            ->descriptions([
                'approved' => 'Authorize the payment for processing',
                'rejected' => 'Decline the payment request',
                'processing' => 'The payment is being processed',
                'completed' => 'The payment has successfully been made',
                'cancelled' => 'The payment has been cancelled',
            ])
            ->inline()
            ->inlineLabel(false)
            ->disableOptionWhen(fn(string $value): bool => PaymentRequestPolicy::updateStatus($value))
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🛑 </span>Status'));
    }


    /**
     * @return Select
     */
    public static function getType(): Select
    {
        return Select::make('type')
            ->options(PaymentRequest::$typesOfPayment)
            ->live()
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⭕ </span>Type<span class="red"> *</span>'));
    }

    /**
     * @return MarkdownEditor
     */
    public static function getPurpose(): MarkdownEditor
    {
        return MarkdownEditor::make('purpose')
            ->label('')
            ->maxLength(65535)
            ->requiredIf('type', 'Other')
            ->columnSpanFull()
            ->disableAllToolbarButtons()
            ->hidden(fn(Get $get): bool => $get('type') != 'Other')
            ->hintColor('primary')
            ->placeholder('Please specify the purpose of payment request')
            ->hint(new HtmlString('<span class="grayscale">🚩 </span>Purpose<span class="red"> *</span>'));
    }

    /**
     * @return MarkdownEditor
     */
    public static function getDescription(): MarkdownEditor
    {
        return MarkdownEditor::make('description')
            ->label('')
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('optional')
            ->columnSpanFull()
            ->hint(new HtmlString('<span class="grayscale">ℹ️ </span>Extra Information'));
    }

    /**
     * @return TextInput
     */
    public static function getPayableAmount(): TextInput
    {
        return TextInput::make('individual_amount')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">💳 </span>Payable amount<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->placeholder('The amount to pay')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getTotalAmount(): TextInput
    {
        return TextInput::make('total_amount')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">💰 </span>Total amount<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->placeholder('Inclusive of the payable amount')
            ->numeric();
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
     * @return DatePicker
     */
    public static function getDeadline(): DatePicker
    {
        return DatePicker::make('deadline')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📅 </span>Deadline<span class="red"> *</span>'))
            ->hintColor('primary')
            ->native(false)
            ->required();
    }

    /**
     * @return TextInput
     */
    public static function getBeneficiaryName(): TextInput
    {
        return TextInput::make('beneficiary_name')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">✒️ </span>Beneficiary Name<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('person or organization')
            ->required()
            ->maxLength(255);
    }


    /**
     * @return TextInput
     */
    public static function getRecipientName(): TextInput
    {
        return TextInput::make('recipient_name')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">✒️ </span>Recipient Name<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('If same, enter the beneficiary\'s name again')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return MarkdownEditor
     */
    public static function getBeneficiaryAddress(): MarkdownEditor
    {
        return MarkdownEditor::make('beneficiary_address')
            ->label('')
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('optional')
            ->hint(new HtmlString('<span class="grayscale">📌 </span>Beneficiary Address'));
    }

    /**
     * @return TextInput
     */
    public static function getBankName(): TextInput
    {
        return TextInput::make('bank_name')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🏛️  </span>Bank Name<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return MarkdownEditor
     */
    public static function getBankAddress(): MarkdownEditor
    {
        return MarkdownEditor::make('bank_address')
            ->label('')
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('optional')
            ->hint(new HtmlString('<span class="grayscale">📌 </span>Bank Address'));
    }

    /**
     * @return TextInput
     */
    public static function getAccountNumber(): TextInput
    {
        return TextInput::make('account_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️⃣ ️ </span>Account No.'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getSwiftCode(): TextInput
    {
        return TextInput::make('swift_code')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>Swift Code'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getIBANCode(): TextInput
    {
        return TextInput::make('IBAN')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>IBAN Code'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getIFSCCode(): TextInput
    {
        return TextInput::make('IFSC')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>IFSC Code'))
            ->hintColor('primary')
            ->placeholder('')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getMICRCode(): TextInput
    {
        return TextInput::make('MICR')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>MICR Code'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->numeric()
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return Select
     */
    public static function getOrderNumber(): Select
    {
        return Select::make('order_id')
            ->options(fn() => Order::where('order_status', '<>', 'closed')->pluck('invoice_number', 'id'))
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🛒 </span>Order<span class="red"> *</span>'));
    }

    /**
     * @return TextColumn
     */
    public static function showInvoiceNumber(): TextColumn
    {
        return TextColumn::make('order.invoice_number')
            ->label('Invoice Number')
            ->grow(false)
            ->sortable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showType(): TextColumn
    {
        return TextColumn::make('type')
            ->label('Payment Type')
            ->grow(false)
            ->formatStateUsing(fn($state) => PaymentRequest::$typesOfPayment[$state])
            ->sortable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showBeneficiaryName(): TextColumn
    {
        return TextColumn::make('beneficiary_name')
            ->label('Beneficiary Name')
            ->color('gray')
            ->grow(false)
            ->sortable()
            ->tooltip('Beneficiary Name')
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showBankName(): TextColumn
    {
        return TextColumn::make('bank_name')
            ->label('Bank Name')
            ->color('gray')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->alignRight()
            ->grow(false)
            ->alignRight()
            ->tooltip('Status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->sortable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPayableAmount(): TextColumn
    {
        return TextColumn::make('individual_amount')
            ->label('Payable Amount')
            ->color('warning')
            ->grow(false)
            ->state(fn(?Model $record) => self::concatenateSum($record))
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showDeadline(): TextColumn
    {
        return TextColumn::make('deadline')
            ->color('danger')
            ->dateTime()
            ->sortable()
            ->badge()
            ->tooltip(fn(?Model $record) => self::showRemainingDays($record))
            ->toggleable(isToggledHiddenByDefault: false)
            ->formatStateUsing(fn(string $state): string => '⏰ Deadline: ' . Carbon::parse($state)->format('M j, Y'));
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
            ->toggleable();
    }

    /**
     * @return Grouping
     */
    public static function filterByType(): Grouping
    {
        return Grouping::make('type')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::$typesOfPayment[$record->type]);
    }

    /**
     * @return Grouping
     */
    public static function filterByStatus(): Grouping
    {
        return Grouping::make('status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->status));
    }

    /**
     * @return Grouping
     */
    public static function filterByOrder(): Grouping
    {
        return Grouping::make('order_id')->label('Order')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->order->invoice_number));
    }

    /**
     * @return Grouping
     */
    public static function filterByCurrency(): Grouping
    {
        return Grouping::make('currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->currency));
    }


    /**
     * @return TextColumn
     */
    public static function showPurpose(): TextColumn
    {
        return TextColumn::make('purpose')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showBeneficiaryAddress(): TextColumn
    {
        return TextColumn::make('beneficiary_address')
            ->label('Beneficiary Address')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showBankAddress(): TextColumn
    {
        return TextColumn::make('bank_address')
            ->label('Bank Address')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showExtraDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }


    /**
     * @return TextColumn
     */
    public static function showAccountNumber(): TextColumn
    {
        return TextColumn::make('account_number')
            ->label('Account Number')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showSwiftCode(): TextColumn
    {
        return TextColumn::make('swift_code')
            ->label('Swift Code')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showIBAN(): TextColumn
    {
        return TextColumn::make('IBAN')
            ->label('IBAN')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showIFSC(): TextColumn
    {
        return TextColumn::make('IFSC')
            ->label('IFSC')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showMICR(): TextColumn
    {
        return TextColumn::make('MICR')
            ->label('MISCR')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }


    /**
     * @return TextEntry
     */
    public static function viewOrder(): TextEntry
    {
        return TextEntry::make('order_id')
            ->label('Order')
            ->state(function (Model $record): string {
                return $record->order->invoice_number;
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewType(): TextEntry
    {
        return TextEntry::make('type')
            ->state(fn(Model $record) => PaymentRequest::$typesOfPayment[$record->type])
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBeneficiaryName(): TextEntry
    {
        return TextEntry::make('beneficiary_name')
            ->label('Beneficiary Name')
            ->color('secondary')
            ->badge();
    }


    /**
     * @return TextEntry
     */
    public static function viewRecipientName(): TextEntry
    {
        return TextEntry::make('recipient_name')
            ->label('Recipient Name')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBankName(): TextEntry
    {
        return TextEntry::make('bank_name')
            ->label('Bank Name')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBeneficiaryAddress(): TextEntry
    {
        return TextEntry::make('beneficiary_address')
            ->label('Beneficiary Address')
            ->columnSpanFull()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPurpose(): TextEntry
    {
        return TextEntry::make('purpose')
            ->color('secondary')
            ->columnSpanFull()
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDescription(): TextEntry
    {
        return TextEntry::make('description')
            ->label('Extra')
            ->columnSpanFull()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBankAddress(): TextEntry
    {
        return TextEntry::make('bank_address')
            ->label('Bank Address')
            ->columnSpanFull()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewAccountNumber(): TextEntry
    {
        return TextEntry::make('account_number')
            ->label('Account Number')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewSwiftCode(): TextEntry
    {
        return TextEntry::make('swift_code')
            ->label('Swift Code')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewIBAN(): TextEntry
    {
        return TextEntry::make('IBAN')
            ->label('IBAN')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewIFSC(): TextEntry
    {
        return TextEntry::make('IFSC')
            ->label('IFSC')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewMICR(): TextEntry
    {
        return TextEntry::make('MICR')
            ->label('MICR')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewStatus(): TextEntry
    {
        return TextEntry::make('status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewAmount(): TextEntry
    {
        return TextEntry::make('amount')
            ->state(function(?Model $record) {return self::concatenateSum($record);})
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDeadline(): TextEntry
    {
        return TextEntry::make('deadline')
            ->state(fn(?Model $record): string => self::showRemainingDays($record))
            ->badge();
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
     * @param Model|null $record
     * @return string
     */
    private static function concatenateSum(?Model $record): string
    {
        return '💰 Sum: ' . number_format($record->individual_amount) . '/' . number_format($record->total_amount) . ' - ' . $record->currency;
    }
}
