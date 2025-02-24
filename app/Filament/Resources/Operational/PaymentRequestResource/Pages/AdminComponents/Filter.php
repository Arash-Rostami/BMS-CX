<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents;

use App\Models\Allocation;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\PaymentRequest;
use App\Models\Supplier;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;


trait Filter
{
    /**
     * @return Grouping
     */
    public static function groupByType(): Grouping
    {
        return Grouping::make('type_of_payment')->collapsible()
            ->label('Type')
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::$typesOfPayment[$record->type_of_payment]);
    }

    /**
     * @return Grouping
     */
    public static function groupByStatus(): Grouping
    {
        return Grouping::make('status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->status));
    }


    /**
     * @return Grouping
     */
    public static function groupByCase(): Grouping
    {
        return Grouping::make('case_number')
            ->label('Case/Contract')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record) => $record->case_number ?? 'No Case/Contract No.');
    }


    /**
     * @return Grouping
     */
    public static function groupByProformaInvoice(): Grouping
    {
        return Grouping::make('proformaInvoices.proforma_number')->label('Pro forma Invoice')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->proforma_invoice_number ?? 'No Pro forma Invoice No');
    }

    /**
     * @return Grouping
     */
    public static function groupByOrder(): Grouping
    {
        return Grouping::make('order.invoice_number')->label('Project')->collapsible()
            ->getTitleFromRecordUsing(function (Model $record): string {
                $order = optional($record->order)->invoice_number ?? null;
                $proformaInvoice = optional($record->associatedProformaInvoices)
                    ? $record->associatedProformaInvoices
                        ->map(fn($invoice) => $invoice->contract_number)
                        ->unique()
                        ->join(', ')
                    : 'No Project No. Given';
                $errorFreeProformaInvoice = empty($proformaInvoice) ? 'No Project No. Given' : $proformaInvoice;
                return $order ?? $errorFreeProformaInvoice;
            });

    }

    /**
     * @return Grouping
     */
    public static function groupByCurrency(): Grouping
    {
        return Grouping::make('currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->currency));
    }

    /**
     * @return Grouping
     */
    public static function groupByDepartment(): Grouping
    {
        return Grouping::make('department_id')->collapsible()
            ->label('Dep.')
            ->getTitleFromRecordUsing(fn(Model $record): string => Department::getByName($record->department_id));
    }

    /**
     * @return Grouping
     */
    public static function groupByReason(): Grouping
    {
        return Grouping::make('reason_for_payment')->collapsible()
            ->label('Reason')
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::showAmongAllReasons($record->reason_for_payment));
    }


    public static function groupByContractor($id = null): Grouping
    {
        return Grouping::make('contractor.name')
            ->collapsible()
            ->label('Contractor')
            ->getTitleFromRecordUsing(function (Model $record) use ($id) {
                if (!$record->contractor) {
                    return 'No contractor';
                }
                $contractorName = $record->contractor->name ?? 'N/A';
                $totals = self::calculateTotals('contractor_id', $record->contractor_id, $id);

                return "{$contractorName} {$totals}";
            });
    }

    public static function groupBySupplier($id = null): Grouping
    {
        return Grouping::make('supplier.name')
            ->collapsible()
            ->label('Supplier')
            ->getTitleFromRecordUsing(function (Model $record) use ($id) {
                if (!$record->supplier) {
                    return 'No supplier';
                }
                $supplierName = $record->supplier->name ?? 'N/A';
                $totals = self::calculateTotals('supplier_id', $record->supplier_id, $id);

                return "{$supplierName} {$totals}";
            });
    }


    /**
     * @return Grouping
     */
    public static function groupByBeneficiary(): Grouping
    {
        return Grouping::make('beneficiary.name')->collapsible()
            ->label('Beneficiary')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->beneficiary?->name ?? 'No beneficiary');
    }


    public static function filterByCurrency(): SelectFilter
    {
        return SelectFilter::make('currency')
            ->label('Currency')
            ->multiple()
            ->options(fn() => collect(showCurrencies())->mapWithKeys(fn($html, $key) => [$key => strip_tags($html->toHtml())]));
    }


    public static function filterByDepartment(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label('Department')
            ->options(Department::getAllDepartmentNames())
            ->multiple()
            ->placeholder('All Departments');
    }

    public static function filterByCostCenter(): SelectFilter
    {
        return SelectFilter::make('cost_center')
            ->label('Cost Center')
            ->options(Department::getAllDepartmentNames())
            ->multiple()
            ->placeholder('All Departments');
    }


    public static function filterByTypeOfPayment(): SelectFilter
    {
        return SelectFilter::make('type_of_payment')
            ->label('Type')
            ->options(PaymentRequest::$typesOfPayment)
            ->multiple()
            ->placeholder('All Types');
    }

    public static function filterByReason(): SelectFilter
    {
        return SelectFilter::make('reason_for_payment')
            ->label('Reason')
            ->options(Allocation::pluck('reason', 'id')->toArray())
            ->multiple()
            ->placeholder('All Types');
    }

    public static function filterByStatus(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options([
                'pending' => 'New',
                'processing' => 'Processing',
                'allowed' => 'Allowed',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ])
            ->multiple()
            ->placeholder('All Statuses');
    }

    public static function filterByUpcomingDeadline(): SelectFilter
    {
        return SelectFilter::make('deadline_filter')
            ->label('Deadline')
            ->options([
                'overdue' => 'Overdue',
                'within_days' => 'Within a few days',
                'within_week' => 'Within a week',
            ])
            ->query(function (Builder $query, $state) {
                if (isset($state['value'])) {
                    $value = $state['value'];
                    switch ($value) {
                        case 'overdue':
                            $query->whereDate('deadline', '<', now());
                            break;

                        case 'within_days':
                            $query->whereBetween('deadline', [now(), now()->addDays(3)]);
                            break;

                        case 'within_week':
                            $query->whereBetween('deadline', [now(), now()->addWeek()]);
                            break;
                    }
                }
            })
            ->default(null)
            ->placeholder('All Deadlines');
    }


    public static function filterBySupplier(): SelectFilter
    {
        return SelectFilter::make('supplier_id')
            ->label('Supplier')
            ->options(function () {
                return PaymentRequest::query()
                    ->with('supplier')
                    ->get()
                    ->pluck('supplier.name', 'supplier.id')
                    ->filter(fn($label) => $label !== null)
                    ->toArray();
            })
//            ->query(function (Builder $query, array $data): Builder {
//                if (isset($data['value'])) {
//                    return $query->whereIn('supplier_id', $data['value']);
//                }
//                return $query;
//            })
            ->multiple()
            ->searchable()
            ->default(null)
            ->placeholder('All Suppliers');
    }

    public static function filterByContractor(): SelectFilter
    {
        return SelectFilter::make('contractor_id')
            ->label('Contractor')
            ->options(function () {
                return PaymentRequest::query()
                    ->with('contractor')
                    ->get()
                    ->pluck('contractor.name', 'contractor.id')
                    ->filter(fn($label) => $label !== null)
                    ->toArray();
            })
//            ->query(function (Builder $query, array $data): Builder {
//                if (isset($data['value'])) {
//                    return $query->whereIn('contractor_id', $data['value']);
//                }
//                return $query;
//            })
            ->multiple()
            ->searchable()
            ->default(null)
            ->placeholder('All Contractors');
    }

    public static function filterByBeneficiary(): SelectFilter
    {
        return SelectFilter::make('payee_id')
            ->label('Recipient')
            ->options(function (Builder $query) {
                return PaymentRequest::query()
                    ->with('beneficiary')
                    ->get()
                    ->pluck('beneficiary.name', 'beneficiary.id')
                    ->filter(fn($option) => $option !== null)
                    ->toArray();
            })
            ->multiple()
            ->default(null)
            ->placeholder('All Beneficiaries');
    }


    public static function filterByCaseNumber(): SelectFilter
    {
        return SelectFilter::make('case_number')
            ->label('Case No.')
            ->options(function (Builder $query) {
                return PaymentRequest::query()
                    ->distinct()
                    ->pluck('case_number', 'case_number')
                    ->filter(fn($option) => $option !== null)
                    ->toArray();
            })
            ->searchable()
            ->default(null)
            ->placeholder('All Cases');
    }


    public static function filterByPaymentMethod(): SelectFilter
    {
        return SelectFilter::make('payment_method_filter')
            ->label('Payment')
            ->options([
                'sheba' => 'SHEBA',
                'bank_account' => 'Bank Account',
                'card_transfer' => 'Card Transfer',
                'cash' => 'Cash',
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (isset($data['value'])) {
                    $jsonPath = "$.paymentMethod";
                    $query->whereRaw(
                        "JSON_EXTRACT(extra, ?) = ?",
                        [$jsonPath, $data['value']]
                    );
                }
                return $query;
            })
            ->searchable()
            ->placeholder('All Methods');
    }

    public static function filterByBankName(): SelectFilter
    {
        return SelectFilter::make('bank_name')
            ->label('Bank Name')
            ->options(function () {
                return PaymentRequest::query()
                    ->select('bank_name')
                    ->distinct()
                    ->whereNotNull('bank_name')
                    ->orderBy('bank_name')
                    ->pluck('bank_name', 'bank_name')
                    ->toArray();
            })
            ->multiple()
            ->searchable()
            ->placeholder('All Banks');
    }
}
