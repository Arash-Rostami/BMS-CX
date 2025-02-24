<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents;

use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\Admin;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

trait Table
{

    public static function showID(): TextColumn
    {
        return TextColumn::make('reference_number')
            ->label(new HtmlString('<span class="text-primary-500 cursor-pointer" title="Record Unique ID">Ref. No. ⋮ ID</span>'))
            ->weight(FontWeight::ExtraLight)
            ->sortable()
            ->formatStateUsing(fn(Model $record) => $record->reference_number ?? sprintf('PI-%s%04d', $record->created_at->format('y'), $record->id))
            ->grow(false)
            ->copyable()
            ->extraAttributes(['class' => 'copyable-content'])
            ->tooltip(fn(?string $state): ?string => ($state) ? "Pro forma Ref. No. / ID" : '')
            ->toggleable()
            ->searchable();
    }

    public static function showProformaNumber(): TextColumn
    {
        return TextColumn::make('proforma_number')
            ->label('Pro forma No.')
            ->color('info')
            ->badge()
            ->icon('heroicon-s-paper-clip')
            ->iconPosition(IconPosition::Before)
            ->grow(false)
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? 'PI: ' : '') . $record->proforma_number ?? null)
            ->tooltip(fn(?string $state): ?string => ($state) ? "Pro forma Invoice Number" : '')
            ->toggleable()
            ->searchable(isIndividual: true);
    }

    /**
     * @return TextColumn
     */
    public static function showProformaDate(): TextColumn
    {
        return TextColumn::make('proforma_date')
            ->label('Pro forma Date')
            ->color('secondary')
            ->badge()
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => "Pro forma Invoice Date")
            ->date()
            ->toggleable()
            ->sortable();
    }


    /**
     * @return TextColumn
     */
    public static function showCategory(): TextColumn
    {
        return TextColumn::make('category.name')
            ->icon('heroicon-o-rectangle-stack')
            ->tooltip(fn(string $state): string => "Category")
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->color('secondary')
            ->searchable()
            ->grow(false)
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showProduct(): TextColumn
    {
        return TextColumn::make('product.name')
            ->icon('heroicon-o-squares-2x2')
            ->grow(false)
            ->tooltip(fn(string $state): string => "Product")
            ->iconPosition(IconPosition::Before)
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? 'Pr: ' : '') . optional($record->product)->name ?? null)
            ->badge()
            ->searchable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showBuyer(): TextColumn
    {
        return TextColumn::make('buyer.name')
            ->label('Buyer')
            ->sortable()
            ->searchable()
            ->grow(false)
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->sortable()
            ->badge()
            ->toggleable(isToggledHiddenByDefault: true)
            ->searchable(query: function (Builder $query, string $search): Builder {
                $normalizedSearch = strtolower($search);
                if (str_contains($normalizedSearch, 'declin') || str_contains($normalizedSearch, 'cancel')) {
                    return $query->where('status', 'like', '%reject%');
                }
                return $query->where('status', 'like', "%{$search}%");
            });
    }

    /**
     * @return TextColumn
     */
    public static function showSupplier(): TextColumn
    {
        return TextColumn::make('supplier.name')
            ->label('Supplier')
            ->sortable()
            ->searchable()
            ->toggleable()
            ->icon('heroicon-o-arrow-up-on-square-stack')
            ->badge()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showGrade(): TextColumn
    {
        return TextColumn::make('grade.name')
            ->icon('heroicon-m-ellipsis-horizontal-circle')
            ->badge()
            ->grow(false)
            ->color('secondary')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? 'Gr: ' : '') . optional($record->grade)->name ?? null)
            ->tooltip('Grade')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showShipmentPart(): TextColumn
    {
        return TextColumn::make('part')
            ->badge()
            ->grow(false)
            ->color('secondary')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '🚢 Part(s): ' : '') . $record->part)
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showContractName(): TextColumn
    {
        return TextColumn::make('contract_number')
            ->label('Contract No.')
            ->color('primary')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->grow(false)
            ->tooltip('Contract/Project No')
            ->toggleable()
            ->sortable();
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
            ->searchable(['first_name', 'middle_name', 'last_name'])
            ->toggleable(isToggledHiddenByDefault: true)
            ->sortable();
    }

    public static function showAssignedTo(): TextColumn
    {
        return TextColumn::make('assignee.fullName')
            ->label('Assigned to')
            ->badge()
            ->color('secondary')
            ->searchable(['first_name', 'middle_name', 'last_name'])
            ->toggleable(isToggledHiddenByDefault: true)
            ->sortable()
            ->default('None');
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Created at')
            ->badge()
            ->date()
            ->color('secondary')
            ->toggleable(isToggledHiddenByDefault: true)
            ->sortable();
    }


    /**
     * @return TextColumn
     */
    public static function showQuantity(): TextColumn
    {
        return TextColumn::make('quantity')
            ->color('info')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '⏲️ Qt: ' : '') . number_format($record->quantity ?? 0))
            ->grow(false)
            ->toggleable()
            ->searchable()
            ->sortable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPrice(): TextColumn
    {
        return TextColumn::make('price')
            ->color('info')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '💰 Pri: ' : '') . number_format($record->price ?? 0))
            ->searchable()
            ->toggleable()
            ->grow(false)
            ->sortable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPercentage(): TextColumn
    {
        return TextColumn::make('percentage')
            ->label('%')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '°/•' : '') . $record->percentage ?? 0)
            ->tooltip('Percentage of payment')
            ->grow(false)
            ->toggleable()
            ->color('secondary')
            ->badge()
            ->html();
    }

    /**
     * @return TextColumn
     */
    public static function showTotal(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Total')
            ->color('info')
            ->state(function (Model $record) {
                $formattedResult = self::computeShareFromTotal($record);
                return getTableDesign() === 'modern' ? '💳 Ttl: ' . $formattedResult : ' ' . $formattedResult;
            })
            ->toggleable()
            ->grow(false)
            ->badge();
    }


    /**
     * @return TextEntry
     */
    public static function viewProformaInvoice(): TextEntry
    {
        return TextEntry::make('proforma_number')
            ->label('Pro forma Invoice No.')
            ->color('secondary')
            ->badge();
    }

    public static function viewContractNumber(): TextEntry
    {
        return TextEntry::make('contract_number')
            ->label('Contract No.')
            ->color('secondary')
            ->badge();
    }

    public static function viewReferenceNumber(): TextEntry
    {
        return TextEntry::make('reference_number')
            ->label('Ref. No. ⋮ ID')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewProformaDate(): TextEntry
    {
        return TextEntry::make('proforma_date')
            ->label('Pro forma Invoice Date')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewCategory(): TextEntry
    {
        return TextEntry::make('category_id')
            ->label('Category')
            ->state(function (Model $record): string {
                return $record->category->name ?? 'N/A';
            })
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewProduct(): TextEntry
    {
        return TextEntry::make('product_id')
            ->label('Product')
            ->state(function (Model $record): string {
                return $record->product->name ?? 'N/A';
            })
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewGrade(): TextEntry
    {
        return TextEntry::make('grade.name')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewStatus(): TextEntry
    {
        return TextEntry::make('status')
            ->label('Status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBuyer(): TextEntry
    {
        return TextEntry::make('buyer_id')
            ->label('Buyer')
            ->state(function (Model $record): string {
                return $record->buyer->name ?? 'N/A';
            })
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewSupplier(): TextEntry
    {
        return TextEntry::make('supplier_id')
            ->label('Supplier')
            ->state(function (Model $record): string {
                return $record->supplier->name ?? 'N/A';
            })
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewQuantity(): TextEntry
    {
        return TextEntry::make('quantity')
            ->label('Quantity')
            ->state(fn(?Model $record) => number_format($record->quantity ?? 0))
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPrice(): TextEntry
    {
        return TextEntry::make('price')
            ->label('Price')
            ->state(fn(?Model $record) => number_format($record->price ?? 0))
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewShipmentPart(): TextEntry
    {
        return TextEntry::make('part')
            ->label('Part')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPercentage(): TextEntry
    {
        return TextEntry::make('percentage')
            ->label('°/•')
            ->color('secondary')
            ->badge();
    }


    public static function viewTotal()
    {
        return TextEntry::make('id')
            ->label('Total')
            ->state(fn(?Model $record) => Admin::computeShareFromTotal($record))
            ->color('secondary')
            ->badge();
    }
}
