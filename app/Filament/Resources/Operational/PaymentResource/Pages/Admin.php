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
            ->options(fn() => PaymentRequest::whereNotIn('status', ['cancelled', 'rejected', 'completed'])->pluck('beneficiary_name', 'id'))
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
        return MarkdownEditor::make('extra')
            ->label('')
            ->maxLength(65535)
            ->columnSpanFull()
            ->callAfterStateUpdated(fn(?Model $record) => json_encode([['notes', $record->extra]]))
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
            ->options([
                'USD' => new HtmlString('<span class="mr-2">🇺🇸</span> Dollar'),
                'EURO' => new HtmlString('<span class="mr-2">🇪🇺</span> Euro'),
                'Yuan' => new HtmlString('<span class="mr-2">🇨🇳</span> Yuan'),
                'Dirham' => new HtmlString('<span class="mr-2">🇦🇪</span> Dirham'),
                'Ruble' => new HtmlString('<span class="mr-2">🇷🇺</span> Ruble'),
                'Rial' => new HtmlString('<span class="mr-2">🇮🇷</span> Rial')
            ])
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
    public static function getAttacment(): FileUpload
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
}
