<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages\AdminComponents;

use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;

trait Filter
{
    /**
     * @return Grouping
     */
    public static function groupProformaInvoiceRecords(): Grouping
    {
        return Grouping::make('extra')
            ->label('Pro forma No.')
            ->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record) => optional($record->extra)['proforma_number'] ?? 'Not Given')
            ->getTitleFromRecordUsing(fn(Model $record): ?string => ucfirst(optional($record->extra)['proforma_number'] ?? 'N/A'));
    }


    /**
     * @return Grouping
     */
    public static function groupCategoryRecords(): Grouping
    {
        return Grouping::make('category_id')->label('Category')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->category->name));
    }

    /**
     * @return Grouping
     */
    public static function groupProductRecords(): Grouping
    {
        return Grouping::make('product_id')->label('Product')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->product->name));
    }

    /**
     * @return Grouping
     */
    public static function groupBuyerRecords(): Grouping
    {
        return Grouping::make('buyer_id')->label('Buyer')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst(optional($record->buyer)->name));
    }

    /**
     * @return Grouping
     */
    public static function groupSupplierRecords(): Grouping
    {
        return Grouping::make('supplier_id')->label('Supplier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst(optional($record->supplier)->name));
    }

    /**
     * @return Grouping
     */
    public static function groupStatusRecords(): Grouping
    {
        return Grouping::make('request_status')->label('Status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->request_status));
    }
}
