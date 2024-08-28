<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages\AdminComponents;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Name;
use App\Models\Supplier;
use App\Models\User;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInOrderRequest;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

trait Form
{
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
            ->live()
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
            ->createOptionUsing(function (array $data): int {
                return Category::create($data)->getKey();
            })
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
            ->relationship('product', 'name',
                function (Builder $query, Get $get) {
                    if ($get('category_id')) {
                        $query->where('category_id', $get('category_id'));
                    }
                }
            )
            ->required()
            ->createOptionForm([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
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
        return MarkdownEditor::make('details.notes')
            ->label('')
            ->placeholder('Optional description')
            ->disableAllToolbarButtons()
            ->columnSpanFull();
    }


    /**
     * @return TextInput
     */
    public static function getProformaNumber(): TextInput
    {
        return TextInput::make('extra.proforma_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️⃣  </span>Pro forma Invoice No.<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public static function getProformaDate(): DatePicker
    {
        return DatePicker::make('extra.proforma_date')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📅 </span>Pro forma Date<span class="red"> *</span>'))
            ->hintColor('primary')
            ->native(false)
            ->required();
    }

    /**
     * @return TextInput
     */
    public static function getGrade(): TextInput
    {
        return TextInput::make('grade')
            ->label('')
            ->required()
            ->placeholder('Enter N/A if unspecified')
            ->hint(new HtmlString('<span class="grayscale">📏 </span>Grade<span class="red"> *</span>'))
            ->hintColor('primary')
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
            ->maxLength(255);
    }

    public static function getPercentage(): TextInput
    {
        return TextInput::make('extra.percentage')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">°/• </span>Initial Percentage<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->placeholder('Enter number without any %')
            ->numeric()
            ->in(range(0, 100))
            ->validationMessages([
                'in' => 'The percentage point should be a number between 0 and 100!',
            ]);
    }

    /**
     * @return TextInput
     */
    public static function getQuantity(): TextInput
    {
        return TextInput::make('quantity')
            ->label('')
            ->placeholder('According to contractual agreement')
            ->hint(new HtmlString('<span class="grayscale">⏲️ </span>Initial Quantity'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getPrice(): TextInput
    {
        return TextInput::make('price')
            ->label('')
            ->placeholder('According to contractual agreement')
            ->hint(new HtmlString('<span class="grayscale">💰 </span>Initial Price'))
            ->hintColor('primary')
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
            ->createOptionUsing(function (array $data): int {
                return Buyer::create($data)->getKey();
            })
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
            ->hint(new HtmlString('<span class="grayscale">📤 </span>Supplier<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
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
            ->createOptionUsing(function (array $data): int {
                return Supplier::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new supplier')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }


    public static function getStatus(): Radio
    {
        return Radio::make('request_status')
            ->label('')
            ->options(self::$statusIconText)
            ->disabled(!User::isUserAuthorizedForOrderStatus())
            ->default('approved');
    }

    /**
     * @return FileUpload
     */
    public static function getFileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/proforma-attachments')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpanFull();
    }

    /**
     * @return Select
     */
    public static function getAttachmentTitle(): Select
    {
        return Select::make('name')
            ->label('')
            ->options(Name::getSortedNamesForModule( 'OrderRequest'))
            ->placeholder('select or make')
            ->hint(new HtmlString('<span class="grayscale">ℹ️️️ </span>Title/Name'))
            ->hintColor('primary')
            ->requiredWith('file_path')
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInOrderRequest)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                TextInput::make('module')
                    ->disabled(true)
                    ->dehydrateStateUsing(fn($state) => $state ?? 'OrderRequest')
                    ->default('Pro forma Invoice')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'OrderRequest';
                return Name::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new title')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            })
            ->columnSpanFull();
    }
}
