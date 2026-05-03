<?php

namespace App\Filament\Resources\Dashboard\ContractOverviewResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Str;

class Admin
{


    public static function filterByBlDate(): SelectFilter
    {
        return SelectFilter::make('bl_date_range')
            ->form([
                DatePicker::make('bl_date_from')->label('BL Date From'),
                DatePicker::make('bl_date_to')->label('BL Date To'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['bl_date_from'], fn($q) => $q->where('bl_date', '>=', $data['bl_date_from']))
                    ->when($data['bl_date_to'], fn($q) => $q->where('bl_date', '<=', $data['bl_date_to']));
            });
    }


    public static function filterByCategory(): SelectFilter
    {
        return SelectFilter::make('category_id')
            ->label('Category')
            ->multiple()
            ->options([
                1 => 'Minerals',
                2 => 'Polymers',
                3 => 'Chemicals',
                4 => 'Petroleum Derivatives',
            ]);
    }


    public static function filterByOrderStatus(): SelectFilter
    {
        return SelectFilter::make('order_status')
            ->label('Order Status')
            ->multiple()
            ->options([
                'processing' => 'Processing',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
                'accounting_review' => 'Review',
                'accounting_approved' => 'Approved',
                'accounting_rejected' => 'Rejected',
            ]);
    }

    public static function filterByPiDate(): SelectFilter
    {
        return SelectFilter::make('pi_date_range')
            ->form([
                DatePicker::make('pi_date_from')->label('PI Date From'),
                DatePicker::make('pi_date_to')->label('PI Date To'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['pi_date_from'], fn($q) => $q->where('pi_date', '>=', $data['pi_date_from']))
                    ->when($data['pi_date_to'], fn($q) => $q->where('pi_date', '<=', $data['pi_date_to']));
            });
    }

    public static function groupByCtNo(): Group
    {
        return Group::make('pi_contract')
            ->label('Contract No')
            ->collapsible();
    }

    public static function showBlDate(): TextColumn
    {
        return TextColumn::make('bl_date')
            ->label('BL Date')
            ->date()
            ->sortable();
    }

    public static function showCategory(): TextColumn
    {
        return TextColumn::make('category_id')
            ->label('Category')
            ->formatStateUsing(fn($state) => match ($state) {
                1 => 'Minerals',
                2 => 'Polymers',
                3 => 'Chemicals',
                4 => 'Petroleum Derivatives',
                default => '—',
            })
            ->searchable(
                query: function (Builder $query, string $search): Builder {
                    $needle = Str::lower(trim($search));
                    if ($needle === '') return $query;

                    $map = [
                        'minerals' => 1,
                        'polymers' => 2,
                        'chemicals' => 3,
                        'petroleum derivatives' => 4,
                        'petroleum' => 4,
                    ];

                    foreach ($map as $k => $v) {
                        if (str_contains($k, $needle)) {
                            return $query->where('category_id', $v);
                        }
                    }

                    if (is_numeric($needle)) {
                        return $query->where('category_id', (int)$needle);
                    }

                    return $query->whereRaw('0 = 1');
                },
                isIndividual: true,
                isGlobal: false
            )
            ->sortable();
    }

    public static function showGrade(): TextColumn
    {
        return TextColumn::make('grade.name')
            ->label('Grade')
            ->sortable()
            ->searchable(
                query: function (Builder $query, string $search): Builder {
                    $s = Str::lower(trim($search));
                    if ($s === '') return $query;
                    return $query->whereRaw('LOWER(grade_name) LIKE ?', ["%{$s}%"]);
                },
                isIndividual: true,
                isGlobal: false
            );
    }

    public static function showOrderPart()
    {
        return TextColumn::make('order_part')
            ->label('Order Part')
            ->placeholder('Not Created Yet!')
            ->clickable(OrderResource::class, 'order_id')
            ->formatStateUsing(fn($state) => isset($state) ? 'Part ' . $state : '')
            ->searchable(isIndividual: true, isGlobal: false)
            ->sortable();
    }

    public static function showOrderQuantity(): TextColumn
    {
        return TextColumn::make('order_detail_quantity')
            ->label('Orders Quantity')
            ->suffix(' mt')
            ->formatStateUsing(fn($state) => $state === null ? '-' : (is_numeric($state) ? number_format((float)$state, 2) : (string)$state))
            ->sortable();
    }

    public static function showOrderStatus(): TextColumn
    {
        return TextColumn::make('order_status')
            ->label('Order Status')
            ->badge()
            ->formatStateUsing(fn($state) => match ($state) {
                'processing' => '🔄 Processing',
                'closed' => '✔️ Closed',
                'cancelled' => '⛔ Cancelled',
                'accounting_review' => '🔍 Review',
                'accounting_approved' => '✅ Approved',
                'accounting_rejected' => '❌ Rejected',
                default => ucfirst($state),
            })
            ->searchable(isIndividual: true, isGlobal: false)
            ->sortable();
    }

    public static function showPiDate(): TextColumn
    {
        return TextColumn::make('pi_date')
            ->label('PI Date')
            ->date()
            ->sortable();
    }

    public static function showPiNo()
    {
        return TextColumn::make('pi_ref')
            ->label('Ref. No. ⋮ ID')
            ->html()
            ->clickable(ProformaInvoiceResource::class, 'pi_id')
            ->searchable(
                query: function (Builder $query, string $search): Builder {
                    $s = Str::lower($search);
                    return $query->whereRaw('LOWER(pi_ref) LIKE ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(pi_contract) LIKE ?', ["%{$s}%"]);
                },
                isIndividual: true,
                isGlobal: true
            )
            ->sortable();
    }

    public static function showPiQuantity(): TextColumn
    {
        return TextColumn::make('pi_quantity')
            ->label('From PI/Contract')
            ->suffix(' mt')
            ->formatStateUsing(fn($state) => $state === null ? '-' : number_format((float)$state, 2))
            ->sortable();
    }

    public static function showShipmentSatus(): TextColumn
    {
        return TextColumn::make('purchase_status_id')
            ->label('Shipment Status')
            ->formatStateUsing(fn($state) => match ($state) {
                1 => '⏳ Pending',
                2 => '🆓 Released',
                3 => '🚧 In Transit',
                4 => '👮 Customs',
                5 => '🚚 Delivered',
                6 => '🚢 Shipped',
                default => '—',
            })
            ->searchable(
                query: function (Builder $query, string $search): Builder {
                    $s = Str::lower($search);
                    $map = [
                        'pending' => 1,
                        'released' => 2,
                        'in transit' => 3,
                        'customs' => 4,
                        'delivered' => 5,
                        'shipped' => 6,
                    ];

                    foreach ($map as $k => $v) {
                        if (str_contains($s, $k)) {
                            return $query->where('purchase_status_id', $v);
                        }
                    }

                    if (is_numeric($search)) {
                        return $query->where('purchase_status_id', (int)$search);
                    }

                    return $query->whereRaw('0 = 1');
                },
                isIndividual: true,
                isGlobal: false
            )
            ->sortable();
    }

}
