<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Models\Packaging;
use App\Models\ProviderList;
use App\Models\QuoteProvider;
use App\Models\QuoteRequest;
use App\Services\PortMaker;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Admin
{


    /**
     * @return TextColumn
     */
    public static function showResponseRate(): TextColumn
    {
        return TextColumn::make('response')
            ->tooltip('Response rate')
            ->label('Response Rate')
            ->state(fn(Model $record) => QuoteRequest::showQuoteResponseRate($record->id))
            ->badge()
            ->grow(false);
    }

    /**
     * @return TextColumn
     */
    public static function showOriginPort(): TextColumn
    {
        return TextColumn::make('origin_port')
            ->label('Origin Port')
            ->badge()
            ->grow(false)
            ->color('warning')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showDestinatonPort(): TextColumn
    {
        return TextColumn::make('destination_port')
            ->label('Destination Port')
            ->badge()
            ->color('success')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showContainerType(): TextColumn
    {
        return TextColumn::make('container_type')
            ->label('Container Type')
            ->color('gray')
            ->grow()
            ->size(TextColumnSize::ExtraSmall)
            ->words(7)
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return ToggleColumn|string|null
     */
    public static function showSwitchBL(): ToggleColumn
    {
        return ToggleColumn::make('requires_switch_bl')
            ->label('Switch BL');
    }

    /**
     * @return TextColumn
     */
    public static function showCommodity(): TextColumn
    {
        return TextColumn::make('commodity')
            ->searchable()
            ->grow(false)
            ->alignRight()
            ->badge()
            ->color('secondary')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showPackaging(): TextColumn
    {
        return TextColumn::make('packing')
            ->label('Packaging')
            ->badge()
            ->grow(false)
            ->alignRight()
            ->color('secondary')
            ->formatStateUsing(fn(string $state) => Packaging::find($state)->name)
            ->sortable()
            ->toggleable();
    }

    /**
     * @return TextColumn
     */
    public static function showGrossWeight(): TextColumn
    {
        return TextColumn::make('gross_weight')
            ->label('Gross Weight')
            ->badge()
            ->color('secondary')
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
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTargetRate(): TextColumn
    {
        return TextColumn::make('target_of_rate')
            ->label('Target Rate')
            ->badge()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTargetTHC(): TextColumn
    {
        return TextColumn::make('target_thc')
            ->label('Target THC')
            ->badge()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTargetLocalCharges(): TextColumn
    {
        return TextColumn::make('target_local_charges')
            ->label('Target Local Charges')
            ->badge()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTargetSwitchBLFee(): TextColumn
    {
        return TextColumn::make('target_switch_bl_fee')
            ->label('Target Switch BL Fee')
            ->badge()
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
            ->color('danger')
            ->tooltip('Valid until ')
            ->formatStateUsing(fn($state) => getTableDesign() == 'modern' ? "Valid until: $state" : $state)
            ->alignRight()
            ->size(TextColumnSize::Small)
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showRequester(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->label('Requester')
            ->color('gray')
            ->grow()
            ->size(TextColumnSize::Small)
            ->searchable(['first_name', 'last_name'])
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return Select
     */
    public static function getQuoteProviders(): Select
    {
        return Select::make('recipient')
            ->label('Quote Providers List')
            ->placeholder('Click providers list to select or deselect related recipients.')
            ->multiple()
            ->options(ProviderList::pluck('name', 'id'));
    }

    /**
     * @return Select
     */
    public static function getOriginPort(): Select
    {
        return Select::make('origin_port')
            ->label('POL')
            ->required()
            ->options(fn() => array_combine(PortMaker::getIranianPorts(), PortMaker::getIranianPorts()));
    }

    /**
     * @return Select
     */
    public static function getDestinationPort(): Select
    {
        return Select::make('destination_port')
            ->label('POD')
            ->required()
            ->options(fn() => array_combine(PortMaker::getChinesePorts(), PortMaker::getChinesePorts()));
    }

    /**
     * @return Select
     */
    public static function getPackaging(): Select
    {
        return Select::make('packing')
            ->label('Packaging')
            ->options(Packaging::pluck('name', 'id'));
    }

    /**
     * @return TextInput
     */
    public static function getContainerType(): TextInput
    {
        return TextInput::make('container_type')
            ->label('Container Type')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getCommodity(): TextInput
    {
        return TextInput::make('commodity')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getGrossWeight(): TextInput
    {
        return TextInput::make('gross_weight')
            ->label('Gross Weight')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getQuantity(): TextInput
    {
        return TextInput::make('quantity')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetRate(): TextInput
    {
        return TextInput::make('target_of_rate')
            ->placeholder('preferably in USD')
            ->label('Target Rate')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetTHC(): TextInput
    {
        return TextInput::make('target_thc')
            ->placeholder('preferably in USD')
            ->label('Target THC')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetLocalCharges(): TextInput
    {
        return TextInput::make('target_local_charges')
            ->placeholder('preferably in USD')
            ->label('Target Local Charges')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetSwitchBL(): TextInput
    {
        return TextInput::make('target_switch_bl_fee')
            ->placeholder('preferably in USD')
            ->label('Target Switch BL Fee')
            ->maxLength(255);
    }

    /**
     * @return DatePicker|string|null
     */
    public static function getValidity(): DatePicker
    {
        return DatePicker::make('validity')
            ->default(now())
            ->label('Validity (of request)');
    }

    /**
     * @return Toggle
     */
    public static function getSwitchBL(): Toggle
    {
        return Toggle::make('requires_switch_bl')
            ->label('Switch BL')
            ->required();
    }

    /**
     * @return Textarea
     */
    public static function getExtraInfo(): Textarea
    {
        return Textarea::make('extra')
            ->placeholder('This field is ONLY for additional quote-specific details. The email format is pre-structured, so do NOT include any email body here.')
            ->maxLength(65535)
            ->columnSpanFull();
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
