<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents;

use App\Models\Department;
use App\Models\PaymentRequest;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;

trait Filter
{
    /**
     * @return Grouping
     */
    public static function filterByType(): Grouping
    {
        return Grouping::make('type_of_payment')->collapsible()
            ->label('Type')
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::$typesOfPayment[$record->type_of_payment]);
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
    public static function filterByCase(): Grouping
    {
        return Grouping::make('extra->caseNumber')->label('Case/Contract')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record) => $record->extra['caseNumber'] ?? 'No Case/Contract No.');
    }

    /**
     * @return Grouping
     */
    public static function filterByProformaInvoice(): Grouping
    {
        return Grouping::make('proformaInvoices.proforma_number')->label('Pro forma Invoice')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->proforma_invoice_number ?? 'No Pro forma Invoice No');
    }

    /**
     * @return Grouping
     */
    public static function filterByOrder(): Grouping
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
    public static function filterByCurrency(): Grouping
    {
        return Grouping::make('currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->currency));
    }

    /**
     * @return Grouping
     */
    public static function filterByDepartment(): Grouping
    {
        return Grouping::make('department_id')->collapsible()
            ->label('Dep.')
            ->getTitleFromRecordUsing(fn(Model $record): string => Department::getByName($record->department_id));
    }

    /**
     * @return Grouping
     */
    public static function filterByReason(): Grouping
    {
        return Grouping::make('reason_for_payment')->collapsible()
            ->label('Reason')
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::showAmongAllReasons($record->reason_for_payment));
    }

    /**
     * @return Grouping
     */
    public static function filterByContractor(): Grouping
    {
        return Grouping::make('contractor.name')->collapsible()
            ->label('Contractor')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->contractor->name ?? 'No contractor');
    }

    /**
     * @return Grouping
     */
    public static function filterBySupplier(): Grouping
    {
        return Grouping::make('supplier.name')->collapsible()
            ->label('Supplier')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->supplier->name ?? 'No supplier');
    }

    /**
     * @return Grouping
     */
    public static function filterByPayee(): Grouping
    {
        return Grouping::make('payee.name')->collapsible()
            ->label('Payee')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->payee->name ?? 'No payee');
    }
}
