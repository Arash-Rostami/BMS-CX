<?php

namespace App\Filament\Resources\Operational\QuoteResource\Pages;

use App\Models\DeliveryTerm;
use App\Models\Packaging;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Admin
{

    /**
     * @return TextColumn
     */
    public static function showQuoteProvider(): TextColumn
    {
        return TextColumn::make('quoteProvider.name')
            ->label('Quote Provider')
            ->badge()
            ->grow(false)
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showCommodity(): TextColumn
    {
        return TextColumn::make('quoteRequest.commodity')
            ->label('Request for')
            ->grow(false)
            ->badge()
            ->color('secondary')
            ->searchable(['commodity'])
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showCommodityType(): TextColumn
    {
        return TextColumn::make('commodity_type')
            ->label('Commodity')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showOriginPort(): TextColumn
    {
        return TextColumn::make('origin_port')
            ->label('POL')
            ->grow(false)
            ->badge()
            ->color('warning')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showDestinationPort(): TextColumn
    {
        return TextColumn::make('destination_port')
            ->label('POD')
            ->grow(false)
            ->badge()
            ->color('success')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTransportationMeans(): TextColumn
    {
        return TextColumn::make('transportation_means')
            ->label('Transportation Means')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTransportationType(): TextColumn
    {
        return TextColumn::make('transportation_type')
            ->label('Transportation Type')
            ->badge()
            ->formatStateUsing(fn(string $state) => DeliveryTerm::find($state)->name)
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showOfferedRate(): TextColumn
    {
        return TextColumn::make('offered_rate')
            ->label('Offered Rate')
            ->grow(false)
            ->alignEnd()
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showSwitchBLFee(): TextColumn
    {
        return TextColumn::make('switch_bl_fee')
            ->label('Switch BL Fee')
            ->grow(false)
            ->alignRight()
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showValidity(): TextColumn
    {
        return TextColumn::make('validity')
            ->date()
            ->alignRight()
            ->tooltip('Deadline')
            ->badge()
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showPackingType(): TextColumn
    {
        return TextColumn::make('packing_type')
            ->label('Packing')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->formatStateUsing(fn(string $state) => Packaging::find($state)->name)
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showPaymentTerms(): TextColumn
    {
        return TextColumn::make('payment_terms')
            ->label('Payment Terms')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showFreeTime(): TextColumn
    {
        return TextColumn::make('free_time_pol')
            ->label('Free Time')
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return IconColumn
     */
    public static function showAttachment(): IconColumn
    {
        return IconColumn::make('attachment.file_path')
            ->tooltip('Attachment')
            ->icon(fn(Model $record) => $record->attachment?->file_path ? 'heroicon-c-check-circle' : 'heroicon-o-no-symbol')
            ->color(fn(Model $record) => $record->attachment?->file_path ? 'success' : 'danger')
            ->default('heroicon-o-no-symbol');
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->alignLeft()
            ->color('gray')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return Filter
     * @throws \Exception
     */
    public static function filterCreatedAt(): Filter
    {
        return Filter::make('created_at')
            ->form([
                DatePicker::make('created_from')
                    ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                DatePicker::make('created_until')
                    ->placeholder(fn($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'] ?? null,
                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'] ?? null,
                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'Order from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                }
                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Order until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }


    /**
     * @return TrashedFilter
     * @throws \Exception
     */
    public static function filterSoftDeletes(): TrashedFilter
    {
        return TrashedFilter::make();
    }
}
