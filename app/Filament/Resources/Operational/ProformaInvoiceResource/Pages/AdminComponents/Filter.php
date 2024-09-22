<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter as FilamentFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


trait Filter
{

    /**
     * @return Grouping
     */
    public static function groupProformaDateRecords(): Grouping
    {
        return Grouping::make('proforma_date')
            ->label('Pro forma Date')
            ->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record) => ($record->proforma_date) ?? 'Not Given')
            ->getTitleFromRecordUsing(fn(Model $record): ?string => ucfirst(($record->proforma_date) ?? 'N/A'));
    }

    /**
     * @return Grouping
     */
    public static function groupProformaInvoiceRecords(): Grouping
    {
        return Grouping::make('proforma_number')
            ->label('Pro forma No.')
            ->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record) => ($record->proforma_number) ?? 'Not Given')
            ->getTitleFromRecordUsing(fn(Model $record): ?string => ucfirst(($record->proforma_number) ?? 'N/A'));
    }


    /**
     * @return Grouping
     */
    public static function groupCategoryRecords(): Grouping
    {
        return Grouping::make('category_id')->label('Category')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->category->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupProductRecords(): Grouping
    {
        return Grouping::make('product_id')->label('Product')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->product->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupBuyerRecords(): Grouping
    {
        return Grouping::make('buyer_id')->label('Buyer')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst(optional($record->buyer)->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupSupplierRecords(): Grouping
    {
        return Grouping::make('supplier_id')->label('Supplier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst(optional($record->supplier)->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupStatusRecords(): Grouping
    {
        return Grouping::make('status')->label('Status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->status ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupContractRecords(): Grouping
    {
        return Grouping::make('contract_number')->label('Contract No.')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->contract_number ?? 'Not Defined'));
    }

    /**
     * @return Grouping
     */
    public static function groupPartRecords(): Grouping
    {
        return Grouping::make('part')->label('Part')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->part ?? 'N/A'));
    }


    public static function filterProforma()
    {
        return FilamentFilter::make('proforma_date')
            ->form([
                DatePicker::make('proforma_from')
                    ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                DatePicker::make('proforma_until')
                    ->placeholder(fn($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['proforma_from'],
                        fn(Builder $query, $date): Builder => $query->whereDate('proforma_date', '>=', $date),
                    )
                    ->when(
                        $data['proforma_until'],
                        fn(Builder $query, $date): Builder => $query->whereDate('proforma_date', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['proforma_from'] ?? null) {
                    $indicators['proforma_from'] = 'Proforma date from ' . Carbon::parse($data['proforma_from'])->toFormattedDateString();
                }
                if ($data['proforma_until'] ?? null) {
                    $indicators['proforma_until'] = 'Proforma date until ' . Carbon::parse($data['proforma_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }
}
