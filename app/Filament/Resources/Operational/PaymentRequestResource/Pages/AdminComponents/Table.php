<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents;

use App\Models\Department;
use App\Models\PaymentRequest;
use App\Policies\PaymentRequestPolicy;
use App\Services\Notification\PaymentRequestService;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use function PHPUnit\Framework\isEmpty;

trait Table
{

    /**
     * @return TextColumn
     */
    public static function showReferenceNumber(): TextColumn
    {
        return TextColumn::make('order.reference_number')
            ->label('PI-/O- Ref. No.')
            ->grow(false)
//            ->searchable()
            ->state(fn(Model $record) => $record->order?->reference_number ? $record->order->reference_number : ($record->department->code != 'CX' ? '🌐' : $record->associatedProformaInvoices->pluck('reference_number')))
            ->tooltip(fn(Model $record) => ($record->department->code != 'CX' ? 'Not related to this department' : 'Ref. No. of PI'))
            ->sortable()
            ->toggleable()
            ->color('info')
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showProformaInvoiceNumber(): TextColumn
    {
        return TextColumn::make('proforma_invoice_number')
            ->label('Proforma Invoice No.')
            ->sortable()
            ->grow(false)
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showInvoiceNumber(): TextColumn
    {
        return TextColumn::make('order.invoice_number')
            ->label('Project No.')
            ->sortable()
            ->state(fn(Model $record) => $record->order?->invoice_number ? $record->order->invoice_number : ($record->department->code != 'CX' ? '🌐' : $record->associatedProformaInvoices->pluck('contract_number')))
            ->color('secondary')
            ->tooltip(fn(Model $record) => ($record->department->code != 'CX' ? 'Not related to this department' : 'Contract No. of PI'))
            ->grow(false)
            ->searchable()
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showPart(): TextColumn
    {
        return TextColumn::make('order.part')
            ->label('Part')
            ->grow(false)
            ->state(fn(Model $record) => $record->order?->part ? $record->order->part : ($record->department->code != 'CX' ? '🌐' : '⭐'))
            ->tooltip(fn(Model $record) => $record->order_id
                ? ($record->order->logistic->booking_number ?? 'Booking Number Unavailable')
                : ($record->order->proformaInvoice->proforma_number ?? 'No Booking Number')
            )
            ->badge()
            ->color(fn(Model $record) => !($record->order_id) ? 'secondary' : 'secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showType(): TextColumn
    {
        return TextColumn::make('type_of_payment')
            ->label('Payment Type')
            ->grow(false)
            ->formatStateUsing(fn($state) => ucwords($state))
            ->sortable()
            ->searchable()
            ->badge();
    }

    public static function showID(): TextColumn
    {
        return TextColumn::make('reference_number')
            ->label(new HtmlString('<span class="text-primary-500 cursor-pointer" title="Record Unique ID">Ref. No. ⋮ ID</span>'))
            ->copyable()
            ->copyMessage('🗐 Ref. No. Copied')
            ->copyMessageDuration(1500)
            ->extraAttributes(['class' => 'copyable-content'])
            ->weight(FontWeight::ExtraLight)
            ->sortable()
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => ($state) ? "Payment Request Ref. No. / ID" : '')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where(function ($query) use ($search) {
                    $search = strtolower($search);
                    return $query
//                        ->whereRaw("DATE_FORMAT(created_at, '%y') = ?", [substr($search, 3, 2)])
//                        ->whereRaw("id = ?", [ltrim(substr($search, 5), '0')])
                        ->orWhereRaw("reference_number LIKE ?", ['%' . $search . '%']);

                });
            });
    }


    /**
     * @return TextColumn
     */
    public static function showDepartment(): TextColumn
    {
        return TextColumn::make('department.code')
            ->label('Dept.')
            ->grow(false)
            ->tooltip(fn(Model $record) => $record->department->name)
            ->color('secondary')
            ->sortable()
            ->searchable(['code', 'name'])
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showCostCenter(): TextColumn
    {
        return TextColumn::make('costCenter.name')
            ->label('Cost Center')
            ->grow(false)
            ->searchable(['name', 'code'])
            ->tooltip(fn($state) => $state ? Department::getByName($state) : 'N/A')
            ->formatStateUsing(fn($state) => $state ?: 'N/A')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showCaseNumber(): TextColumn
    {
        return TextColumn::make('extra.caseNumber')
            ->label('Case/Contract No.')
            ->grow(false)
            ->searchable()
            ->color('secondary')
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showReasonForPayment(): TextColumn
    {
        return TextColumn::make('reason.reason')
            ->label('Reason')
            ->sortable()
            ->searchable()
            ->badge()
            ->limit(20)
            ->tooltip(fn($record) => $record->reason->reason);
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
            ->state(function (Model $record) {
                return $record->contractor?->name
                    ?? $record->supplier?->name
                    ?? $record->beneficiary?->name
                    ?? null;
            })->tooltip('Beneficiary Name')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where(function ($query) use ($search) {
                    $query->whereHas('contractor', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%' . $search . '%');
                    })
                        ->orWhereHas('supplier', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('beneficiary', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->limit(25)
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showBankName(): TextColumn
    {
        return TextColumn::make('bank_name')
            ->label('Bank Name')
            ->lineClamp(1)
            ->color('gray')
            ->sortable()
            ->grow(false)
            ->toggleable()
            ->limit(20)
            ->tooltip(fn($record) => $record ? $record->bank_name : 'Not given')
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->label(fn() => new HtmlString('<span class="bg-primary-500 p-2 rounded-xl shadow-2xl text-white">⚙️ Status</span>'))
            ->alignRight()
            ->grow(false)
            ->alignRight()
            ->tooltip('⇄ Change status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->action(
                Action::make('select')
                    ->label('Change Status')
                    ->modalHeading('Change Status')
                    ->modalWidth('md')
                    ->modalIcon('heroicon-o-pencil-square')
                    ->modalSubmitActionLabel('Save')
                    ->form([
                        Select::make('status')
                            ->label('')
                            ->options([
                                'processing' => 'Pending ⌛',
                                'allowed' => 'Allow ✔️ ',
                                'approved' => 'Approve ✔️✔️',
                                'rejected' => 'Deny ⛔',
                            ])
                            ->disableOptionWhen(fn(string $value, Model $record): bool => PaymentRequestPolicy::updateStatus($value, $record))
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->update(['status' => $data['status']]);
                        $service = new PaymentRequestService();
                        $accountants = $record->user ? collect([$record->user]) : collect();
                        $service->notifyAccountants($record, type: $data['status'], status: true, accountants: $accountants);
                        Notification::make()
                            ->title('Status updated: ' . ucfirst($data['status']))
                            ->success()
                            ->send();
                    }),
            )
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPayableAmount(): TextColumn
    {
        return TextColumn::make('requested_amount')
            ->label('Payable Amount')
            ->color('warning')
            ->grow(false)
            ->state(fn(?Model $record) => self::concatenateSum($record))
            ->sortable()
            ->toggleable()
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
            ->formatStateUsing(fn(string $state): string => '📅 Deadline: ' . Carbon::parse($state)->format('M j, Y'));
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
            ->fontFamily(FontFamily::Mono)
            ->copyable()
            ->copyMessage('🗐 Account No. Copied')
            ->copyMessageDuration(1500)
            ->extraAttributes(['class' => 'copyable-content cell'])
            ->formatStateUsing(function ($state, $record) {
                if (!$record) {
                    return $state;
                }

                $paymentMethod = data_get($record, 'extra.paymentMethod');
                if ($paymentMethod === 'sheba') {
                    return 'IR' . $state;
                }

                return $state;
            })
            ->tooltip(fn($record) => match (data_get($record, 'extra.paymentMethod')) {
                'sheba' => 'Payment Method: SHEBA',
                'card_transfer' => 'Payment Method: Card Transfer',
                'bank_account' => 'Payment Method: Bank Account',
                'cash' => 'Payment Method: Cash',
                default => 'Payment Method: Unlisted',
            })
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
     * @return TextColumn
     */
    public static function showCreator(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->label('Created by')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showRequestMaker(): TextColumn
    {
        return TextColumn::make('extra.made_by')
            ->label('Made By')
            ->tooltip(fn(Model $record) => $record->created_at)
            ->color('secondary')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.made_by')) LIKE ?", ["%{$search}%"]);
            })
            ->sortable(query: function (Builder $query, string $direction) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.made_by')) $direction");
            })
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showStatusChanger(): TextColumn
    {
        return TextColumn::make('extra.statusChangeInfo.changed_by')
            ->tooltip(fn(Model $record) => Arr::get($record->extra, 'statusChangeInfo.changed_at', 'No status change recorded'))
            ->label('Status Changed By')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.statusChangeInfo.changed_by')) LIKE ?", ["%{$search}%"]);
            })
            ->sortable(query: function (Builder $query, string $direction) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.statusChangeInfo.changed_by')) $direction");
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewOrder(): TextEntry
    {
        return TextEntry::make('order_invoice_number')
            ->label('Order')
            ->default('N/A')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPart(): TextEntry
    {
        return TextEntry::make('part')
            ->label('Part')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewType(): TextEntry
    {
        return TextEntry::make('type_of_payment')
            ->color('secondary')
            ->state(fn(Model $record) => PaymentRequest::$typesOfPayment[$record->type_of_payment])
            ->badge();
    }

    public static function viewReason(): TextEntry
    {
        return TextEntry::make('reason_for_payment')
            ->state(fn(Model $record) => PaymentRequest::showAmongAllReasons($record->reason_for_payment))
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
            ->state(function (Model $record) {
                return $record->contractor?->name
                    ?? $record->supplier?->name
                    ?? $record->beneficiary?->name
                    ?? null;
            })
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
    public static function viewDepartment(): TextEntry
    {
        return TextEntry::make('department.code')
            ->color('secondary')
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
            ->formatStateUsing(function ($state, $record) {
                if (!$record) {
                    return $state;
                }

                $paymentMethod = data_get($record, 'extra.paymentMethod');
                if ($paymentMethod === 'unlisted') {
                    return 'Unavailable';
                }
                if ($paymentMethod === 'sheba') {
                    return 'IR' . $state;
                }

                return $state;
            })
            ->tooltip(fn($record) => match (data_get($record, 'extra.paymentMethod')) {
                'sheba' => 'Payment Method: SHEBA',
                'card_transfer' => 'Payment Method: Card Transfer',
                'bank_account' => 'Payment Method: Bank Account',
                default => 'Payment Method: Unlisted',
            })
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
            ->state(function (?Model $record) {
                return self::concatenateSum($record);
            })
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
}
