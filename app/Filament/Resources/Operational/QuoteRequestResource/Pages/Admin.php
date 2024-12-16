<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Forms\Components\InsertTemplateField;
use App\Models\Packaging;
use App\Models\ProviderList;
use App\Models\QuoteProvider;
use App\Models\QuoteRequest;
use App\Services\PortMaker;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Wallo\FilamentSelectify\Components\ToggleButton;

class Admin
{

    /**
     * @return Toggle|string|null
     */
    public static function getMarkDown(): Toggle
    {
        return Toggle::make('extra.use_markdown')
            ->columnSpanFull()
            ->live()
            ->afterStateUpdated(fn(Set $set) => $set('extra.details', ''))
            ->label('Use simple/markdown format');
    }

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
    public static function showTitle(): TextColumn
    {
        return TextColumn::make('extra.title')
            ->label('Title')
            ->searchable()
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
    public static function showDestinationPort(): TextColumn
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
            ->disabled()
            ->label('Switch BL');
    }

    /**
     * @return TextColumn
     */
    public static function showCommodity(): TextColumn
    {
        return TextColumn::make('product.name')
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
     * @return TextInput
     */
    public static function getTitle(): TextInput
    {
        return TextInput::make('extra.title')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Title</span>'))
            ->columnSpan(1)
            ->placeholder('Enter a name or ID for easy reference (e.g., project number, case number, ...)')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return Select
     */
    public static function getQuoteProviders(): Select
    {
        return Select::make('extra.recipient')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Quote Providers List</span>'))
            ->placeholder('Click providers list to select or deselect related recipients.')
            ->columnSpan(2)
            ->multiple()
            ->required()
            ->options(ProviderList::pluck('name', 'id'));
    }

    /**
     * @return Select
     */
    public static function getOriginPort(): Select
    {
        return Select::make('origin_port')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">POL</span>'))
            ->required()
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->options(fn() => array_combine(PortMaker::getIranianPorts(), PortMaker::getIranianPorts()));
    }

    /**
     * @return Select
     */
    public static function getDestinationPort(): Select
    {
        return Select::make('destination_port')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">POD</span>'))
            ->required()
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->options(fn() => array_combine(PortMaker::getChinesePorts(), PortMaker::getChinesePorts()));
    }

    /**
     * @return Select
     */
    public static function getPackaging(): Select
    {
        return Select::make('packing')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Packaging</span>'))
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->options(Packaging::pluck('name', 'id'));
    }

    public static function getContainerType(): Select
    {
        return Select::make('container_type')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Container Type</span>'))
            ->options([
                'Standard' => ['20-foot Standard' => '20-foot Standard', '40-foot Standard' => '40-foot Standard', '40-foot High Cube' => '40-foot High Cube', '45-foot High Cube' => '45-foot High Cube'],
                'Specialized' => ['Refrigerated (Reefer)' => 'Refrigerated (Reefer)', 'Open Top' => 'Open Top', 'Flat Rack' => 'Flat Rack', 'ISO Tank' => 'ISO Tank', 'Ventilated' => 'Ventilated', 'Insulated/Thermal' => 'Insulated/Thermal']
            ])
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->searchable()
            ->placeholder('Select Container Type');
    }

    /**
     * @return Select
     */
    public static function getCommodity(): Select
    {
        return Select::make('commodity')
            ->relationship('product', 'name')
            ->required()
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Commodity</span>'))
            ->placeholder('Select Container Type');
    }


    /**
     * @return TextInput
     */
    public static function getGrossWeight(): TextInput
    {
        return TextInput::make('gross_weight')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Gross Weight</span>'))
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getQuantity(): TextInput
    {
        return TextInput::make('quantity')
            ->placeholder('Needed number of containers (optional)')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">No of Container</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetRate(): TextInput
    {
        return TextInput::make('target_of_rate')
            ->placeholder('preferably in USD')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Target Rate</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetTHC(): TextInput
    {
        return TextInput::make('target_thc')
            ->placeholder('preferably in USD')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Target THC</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetLocalCharges(): TextInput
    {
        return TextInput::make('target_local_charges')
            ->placeholder('preferably in USD')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Target Local Charges</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getTargetSwitchBL(): TextInput
    {
        return TextInput::make('target_switch_bl_fee')
            ->placeholder('preferably in USD')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Target Fee for Switch BL</span>'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker|string|null
     */
    public static function getValidity(): DatePicker
    {
        return DatePicker::make('validity')
            ->default(now())
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Validity (of request)</span>'));
    }

    /**
     * @return ToggleButton
     */

    public static function getSwitchBL(): ToggleButton
    {
        return ToggleButton::make('requires_switch_bl')
            ->offColor('primary')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Switch Bill of Lading </span>'));
    }

    /**
     * @return Textarea
     */
    public static function getExtraInfo(): Textarea
    {
        return Textarea::make('extra.details')
            ->live()
            ->visible(fn(Get $get) => !$get('extra.use_markdown'))
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Extra Details</span>'))
            ->placeholder(fn() => "Use this field to provide any extra information relevant to the quote, such as specific requirements or preferences. Please avoid adding email body text as the email format is automatically written.")
            ->maxLength(65535)
            ->columnSpanFull();
    }

    public static function getTemplateField()
    {
        return InsertTemplateField::make('template_inserter')
            ->live()
            ->visible(fn(Get $get) => $get('extra.use_markdown'))
            ->label('🪄 AI-Powered Templates');
    }


    /**
     * @return RichEditor
     */
    public static function getEmailBody(): RichEditor
    {
        return RichEditor::make('extra.details')
            ->live()
            ->visible(fn(Get $get) => $get('extra.use_markdown'))
            ->disableToolbarButtons(['blockquote', 'strike', 'italic', 'attachFiles', 'codeBlock'])
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Email Body</span>'))
            ->placeholder(fn() => "⚠️ Please write no greeting or names!")
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
