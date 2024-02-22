<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Rules\EnglishAlphabet;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Admin
{
    /**
     * @return \Closure
     */
    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name;

            $invoiceNumber = $livewire->data['invoice_number'] ?? 'Unknown-Invoice';

            // File extension
            $extension = $file->getClientOriginalExtension();

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            // New filename with extension
            $newFileName = "Payment-{$invoiceNumber}-{$timestamp}-{$name}";

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }

    /**
     * @return Select
     */
    public static function getOrder(): Select
    {
        return Select::make('order_id')
            ->options(fn() => Order::where('order_status', '<>', 'closed')->pluck('invoice_number', 'id'))
            ->afterStateUpdated(function (Set $set, $state) {
                $order = Order::find($state);
                if ($order) {
                    $set('invoice_number', $order->invoice_number);
                }
            })
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🛒 </span>Order<span class="red"> *</span>'));
    }

    /**
     * @return Select
     */
    public static function getPaymentRequest(): Select
    {
        return Select::make('payment_request_id')
            ->options(fn() => PaymentRequest::showApproved())
            ->required()
            ->multiple(fn($operation) => $operation == 'create')
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
        return MarkdownEditor::make('extra')
            ->label('')
            ->maxLength(65535)
            ->columnSpanFull()
//            ->afterStateUpdated(fn(?Model $record) => json_encode(['notes' => $record->extra]))
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
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'])
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
    public static function showPaymentRequest(): TextColumn
    {
        return TextColumn::make('paymentRequests.type')
            ->label('Payment Request')
            ->grow(false)
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
            ->toggleable();
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
            ->color('warning')
            ->grow(false)
            ->state(fn(?Model $record) => '💰 Sum: ' . number_format($record->amount) . ' - ' . $record->currency)
            ->sortable()
            ->searchable()
            ->badge();
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
    public static function filterByOrder(): Grouping
    {
        return Grouping::make('order_id')->collapsible()
            ->label('Order')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->order->invoice_number);
    }

    /**
     * @return Grouping
     */
    public static function filterByPaymentRequest(): Grouping
    {
        return Grouping::make('payment_request_id')->collapsible()
            ->label('Payment Request')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->paymentRequests[0]->beneficiary_name);
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
    public static function viewPaymentRequest(): TextEntry
    {
        return TextEntry::make('payment_request_id')
            ->label('Payment Request')
            ->state(function (Model $record) {
                $invoiceNumbers = [];
                foreach ($record->paymentRequests as $request) {
                    if ($request->type) { // Handle potentially null values
                        $invoiceNumbers[] = PaymentRequest::$typesOfPayment[$request->type];
                    }
                }
                return implode('|', $invoiceNumbers);
            })
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
            ->state(fn(?Model $record) => '💰 Sum: ' . number_format($record->amount) . ' - ' . $record->currency)
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
}
