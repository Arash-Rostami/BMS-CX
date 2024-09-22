<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents;

use App\Models\Name;
use App\Models\PaymentRequest;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInPayment;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

trait Form
{
    /**
     * @return Select
     */
    public static function getPaymentRequest(): Select
    {
        return Select::make('paymentRequests.id')
            ->relationship('paymentRequests',
                'reference_number',
                modifyQueryUsing: function (Builder $query, string $operation) {
                    if ($operation == 'create') {
                        $query->whereIn('status', ['processing', 'approved', 'allowed'])->orderBy('deadline', 'asc');
                    }
                })
            ->getOptionLabelFromRecordUsing(fn(Model $record, $operation) => $record->getCustomizedDisplayName())
            ->searchable(['reference_number', 'proforma_invoice_number'])
            ->disabled(fn($operation) => $operation == 'edit')
            ->afterStateUpdated(fn($state, Set $set) => static::updateRequestedAmount($state, $set))
            ->live()
            ->multiple()
            ->required()
            ->columnSpanFull()
            ->label(fn()=>new HtmlString('<span class="grayscale">💳  </span><span class="text-primary-500 font-normal">Payment Request</span>'));
    }


    /**
     * @return TextInput
     */
    public static function getTransactionID(): TextInput
    {
        return TextInput::make('transaction_id')
            ->label(fn()=>new HtmlString('<span class="grayscale"> #️⃣ </span><span class="text-primary-500 font-normal">Transaction ID</span>'))
            ->placeholder('Optional: Confirmation Code');
    }

    /**
     * @return DatePicker
     */
    public static function getDate(): DatePicker
    {
        return DatePicker::make('date')
            ->label(fn()=>new HtmlString('<span class="grayscale">📆️ </span><span class="text-primary-500 font-normal">Transfer Date</span>'));
    }

    /**
     * @return MarkdownEditor
     */
    public static function getNotes(): MarkdownEditor
    {
        return MarkdownEditor::make('notes')
            ->label(fn()=>new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Notes</span>'))
            ->maxLength(65535)
            ->columnSpanFull()
            ->disableAllToolbarButtons()
            ->placeholder('Optional');
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->options(showCurrencies())
            ->required()
            ->label(fn()=>new HtmlString('<span class="grayscale"> 💱</span><span class="text-primary-500 font-normal">Currency</span>'));
    }

    /**
     * @return TextInput
     */
    public static function getAmount(): TextInput
    {
        return TextInput::make('amount')
            ->label(fn()=>new HtmlString('<span class="grayscale">💰 </span><span class="text-primary-500 font-normal">Amount</span>'))
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
            ->label(fn()=>new HtmlString('<span class="grayscale">✒️  </span><span class="text-primary-500 font-normal">Payer</span>'))
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
            ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
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
     * @return Select
     */
    public static function getTitleOfAttachment(): Select
    {
        return Select::make('name')
            ->options(Name::getSortedNamesForModule('Payment'))
            ->label(fn()=>new HtmlString('<span class="grayscale">ℹ️️️ </span><span class="text-primary-500 font-normal">Title|Name</span>'))
            ->placeholder('Type in English ONLY')
            ->requiredWith('file_path')
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInPayment)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                Hidden::make('module')
                    ->dehydrateStateUsing(fn($state) => $state ?? 'Payment')
                    ->default('Payment')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'Payment';
                return Name::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new title')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            })
            ->rule(new EnglishAlphabet)
            ->columnSpanFull();
    }
}
