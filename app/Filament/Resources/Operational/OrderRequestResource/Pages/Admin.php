<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Models\Buyer;
use App\Models\Supplier;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class Admin
{

    protected static array $statusTexts = [
        'pending' => 'Pending',
        'review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'fulfilled' => 'Fulfilled',
    ];

    protected static array $statusIcons = [
        'pending' => 'heroicon-s-clock',
        'review' => 'heroicon-o-chat-bubble-bottom-center-text',
        'approved' => 'heroicon-s-check-circle',
        'rejected' => 'heroicon-s-x-circle',
        'fulfilled' => 'heroicon-s-trophy',
    ];

    protected static array $statusColors = [
        'pending' => 'warning',
        'review' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'fulfilled' => 'secondary',
    ];

    /**
     * @return Select
     */
    public static function getCategory(): Select
    {
        return Select::make('category_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📂 </span>Category<span class="red"> *</span>'))
            ->hintColor('primary')
            ->relationship('category', 'name')
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => ucwords($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
                    ->unique()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new category')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getProduct(): Select
    {
        return Select::make('product_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📦 </span>Product<span class="red"> *</span>'))
            ->hintColor('primary')
            ->relationship('product', 'name')
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
                    ->unique()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new product')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return MarkdownEditor
     */
    public static function getDetails(): MarkdownEditor
    {
        return MarkdownEditor::make('details')
            ->label('Description')
            ->disableAllToolbarButtons()
            ->columnSpanFull();
    }

    /**
     * @return TextInput
     */
    public static function getGrade(): TextInput
    {
        return TextInput::make('grade')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Grade (needed)'))
            ->hintColor('primary')
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getQuantity(): TextInput
    {
        return TextInput::make('quantity')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Quantity (needed)'))
            ->hintColor('primary')
            ->required()
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getPrice(): TextInput
    {
        return TextInput::make('price')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Price (expected)'))
            ->hintColor('primary')
            ->required()
            ->numeric();
    }

    /**
     * @return Select
     */
    public static function getBuyer(): Select
    {
        return Select::make('buyer_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📥 </span>Buyer<span class="red"> *</span>'))
            ->hintColor('primary')
            ->options(Buyer::all()->pluck('name', 'id'))
            ->searchable()
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
                    ->unique()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new buyer')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getSupplier(): Select
    {
        return Select::make('supplier_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📤 </span>Supplier'))
            ->hintColor('primary')
            ->options(Supplier::all()->pluck('name', 'id'))
            ->searchable()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
                    ->unique()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new supplier')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Radio
     */
    public static function getStatus(): Radio
    {
        return Radio::make('request_status')
            ->label('')
            ->options([
                'pending' => '⏳ Pending',
                'review' => '⚠ Under Review',
                'approved' => '✅ Approved',
                'rejected' => '❌ Rejected',
                'fulfilled' => '🏁 Fulfilled',
            ])
            ->required();
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
        return TextColumn::make('buyer_id')
            ->label('Buyer')
            ->state(function (Model $record): string {
                return $record->buyer->name;
            })
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
        return TextColumn::make('supplier_id')
            ->label('Supplier')
            ->state(function (Model $record): string {
                return $record->supplier->name;
            })
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
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '⏲️ Qt: ' : '') . $record->quantity)
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
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? '💰 Pr: ' : '') . $record->price)
            ->toggleable()
            ->grow(false)
            ->searchable()
            ->sortable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable()
            ->alignRight();
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
            ->color('secondary')
            ->badge();
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
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->buyer->name));
    }

    /**
     * @return Grouping
     */
    public static function groupSupplierRecords(): Grouping
    {
        return Grouping::make('supplier_id')->label('Supplier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->supplier->name));
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
