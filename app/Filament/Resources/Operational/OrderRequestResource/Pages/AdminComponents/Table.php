<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages\AdminComponents;

use App\Filament\Resources\Operational\OrderRequestResource\Pages\Admin;
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
        return TextColumn::make('updated_at')
            ->label(new HtmlString('<span class="text-primary-500 cursor-pointer" title="Record Unique ID">Ref. No. ⋮ ID</span>'))
            ->weight(FontWeight::ExtraLight)
            ->sortable()
            ->formatStateUsing(fn(Model $record) => $record->extra['reference_number'] ?? sprintf('PI-%s%04d', $record->created_at->format('y'), $record->id))
//            ->extraAttributes(function (Model $record) {
//                $actualValue = $record->extra['reference_number'] ?? sprintf('PI-%s%04d', $record->created_at->format('y'), $record->id);
//                return [
//                    'style' => 'display: inline-block; cursor: pointer; font-size: 12px; color:#AD8493; opacity: 0.5; transition: opacity 1s ease;',
//                    'onmouseover' => "this.style.opacity = '1'; this.textContent = '$actualValue';",
//                    'onmouseout' => "this.style.opacity = '0.3'; this.textContent = '****';",
//                ];
//            })
//            ->formatStateUsing(fn() => '****')
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => ($state) ? "Pro forma Ref. No. / ID" : '')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where(function ($query) use ($search) {
                    $search = strtolower($search);
                    if (str_starts_with($search, 'pi-')) {
                        return $query->whereRaw("DATE_FORMAT(created_at, '%y') = ?", [substr($search, 3, 2)])
                            ->whereRaw("id = ?", [ltrim(substr($search, 5), '0')]);
                    } else {
                        return $query->whereRaw("LOWER(json_extract(extra, '$.reference_number')) LIKE ?", ['%' . $search . '%']);
                    }
                });
            });
    }

    public static function showProformaNumber(): TextColumn
    {
        return TextColumn::make('extra.proforma_number')
            ->label('Pro forma No.')
            ->color('info')
            ->badge()
            ->icon('heroicon-s-paper-clip')
            ->iconPosition(IconPosition::Before)
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => ($state) ? "Pro forma Invoice Number" : '')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereRaw("LOWER(json_extract(extra, '$.proforma_number')) LIKE ?", ['%' . strtolower($search) . '%']);
            }, isIndividual: true);
    }

    /**
     * @return TextColumn
     */
    public static function showProformaDate(): TextColumn
    {
        return TextColumn::make('extra.proforma_date')
            ->label('Pro forma Date')
            ->color('secondary')
            ->badge()
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => "Pro forma Invoice Date")
            ->date()
            ->toggleable()
            ->sortable(query: function (Builder $query, string $direction) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.proforma_date')) $direction");
            });
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
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('request_status')
            ->label('Status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->sortable()
            ->badge()
            ->toggleable()
            ->searchable();
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
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showGrade(): TextColumn
    {
        return TextColumn::make('grade')
            ->badge()
            ->grow(false)
            ->color('secondary')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '📏 Gr: ' : '') . $record->grade)
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showQuantity(): TextColumn
    {
        return TextColumn::make('quantity')
            ->color('info')
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '⏲️ Qt: ' : '') . number_format($record->quantity))
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
        return TextColumn::make('extra.percentage')
            ->label('%')
            ->tooltip('Percentage of payment')
            ->grow()
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
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->icon('heroicon-s-calendar-days')
            ->sortable()
            ->toggleable()
            ->alignRight();
    }

    /**
     * @return TextEntry
     */
    public static function viewProformaInvoice(): TextEntry
    {
        return TextEntry::make('extra.proforma_number')
            ->label('Pro forma Invoice No.')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewProformaDate(): TextEntry
    {
        return TextEntry::make('extra.proforma_date')
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
                return $record->category->name;
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
                return $record->product->name;
            })
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewGrade(): TextEntry
    {
        return TextEntry::make('grade')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewStatus(): TextEntry
    {
        return TextEntry::make('request_status')
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
                return $record->buyer->name;
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
                return $record->supplier->name;
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
            ->state(fn(?Model $record) => number_format($record->quantity))
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
            ->state(fn(?Model $record) => number_format($record->price))
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewPercentage(): TextEntry
    {
        return TextEntry::make('extra.percentage')
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
