<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Name;
use App\Models\PortOfDelivery;
use App\Models\ProformaInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInOrderRequest;
use App\Rules\UniqueTitleInProformaInvoice;
use App\Services\ProjectNumberGenerator;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;


trait Form
{
    /**
     * @return Select
     */
    public static function getCategory(): Select
    {
        return Select::make('category_id')
            ->label(fn() => new HtmlString('<span class="grayscale">📂 </span><span class="text-primary-500 font-normal">Category</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">📦 </span><span class="text-primary-500 font-normal">Product</span>'))
            ->relationship('product', 'name',
                function (Builder $query, Get $get) {
                    if (!is_null($get('category_id'))) {
                        $query->where('category_id', $get('category_id'));
                    }
                }
            )
            ->afterStateUpdated(function ($state, Set $set) {
                $set('grade_id', null);
            })
            ->live()
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
     * @return Select
     */
    public static function getShipmentPart(): Select
    {
        return Select::make('part')
            ->label(fn() => new HtmlString('<span class="grayscale">🚢 </span><span class="text-primary-500 font-normal">Total Parts</span>'))
            ->options(array_combine(range(1, 99), range(1, 99)))
            ->placeholder('');
    }


    /**
     * @return Select
     */
    public static function getAssignee(): Select
    {
        return Select::make('assignee_id')
            ->label(fn() => new HtmlString('<span class="grayscale">👤 </span><span class="text-primary-500 font-normal">Assigned to</span>'))
            ->options(function () {
                return User::query()
                    ->select(['id', 'first_name', 'middle_name', 'last_name'])
                    ->where('status', 'active')
                    ->whereJsonContains('info->department', '6')
                    ->orderBy('first_name')
                    ->get()
                    ->mapWithKeys(fn($user) => [$user->id => $user->full_name])
                    ->toArray();
            })
            ->default(auth()->id())
            ->placeholder('Select one member');
    }


    /**
     * @return MarkdownEditor
     */
    public static function getDetails(): MarkdownEditor
    {
        return MarkdownEditor::make('details.notes')
            ->label(fn() => new HtmlString('<span class="grayscale">📝  </span><span class="text-primary-500 font-normal">Notes</span>'))
            ->placeholder('Optional description')
            ->disableAllToolbarButtons()
            ->columnSpanFull();
    }


    /**
     * @return TextInput
     */
    public static function getProformaNumber(): TextInput
    {
        return TextInput::make('proforma_number')
            ->label(fn() => new HtmlString('<span class="grayscale">#️⃣  </span><span class="text-primary-500 font-normal">Pro forma Invoice No.</span>'))
            ->placeholder('')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public static function getProformaDate(): DatePicker
    {
        return DatePicker::make('proforma_date')
            ->label(fn() => new HtmlString('<span class="grayscale">📅 </span><span class="text-primary-500 font-normal">Pro forma Date</span>'))
            ->native(false)
            ->required();
    }

    /**
     * @return Select
     */
    public static function getGrade(): Select
    {
        return Select::make('grade_id')
            ->label(fn() => new HtmlString('<span class="grayscale">♠️ </span><span class="text-primary-500 font-normal">Grade</span>'))
            ->relationship('grade', 'name',
                function (Builder $query, Get $get) {
                    if (!is_null($get('product_id'))) {
                        $query->where('product_id', $get('product_id'));
                    }
                }
            )
            ->live()
            ->reactive()
            ->default(0)
            ->createOptionForm([
                Select::make('product_id')
                    ->relationship('product', 'name')
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
                    ->modalHeading('Create new grade')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    public static function getPercentage(): TextInput
    {
        return TextInput::make('percentage')
            ->label(fn() => new HtmlString('<span class="grayscale">°/• </span><span class="text-primary-500 font-normal">Initial Percentage</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">⏲️ </span><span class="text-primary-500 font-normal">Initial Quantity (mt)</span>'))
            ->placeholder('According to contractual agreement')
            ->required()
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getPrice(): TextInput
    {
        return TextInput::make('price')
            ->label(fn() => new HtmlString('<span class="grayscale">💰 </span><span class="text-primary-500 font-normal">Initial Unit Price</span>'))
            ->placeholder('According to contractual agreement')
            ->required()
            ->numeric();
    }

    /**
     * @return Select
     */
    public static function getBuyer(): Select
    {
        return Select::make('buyer_id')
            ->label(fn() => new HtmlString('<span class="grayscale">📥 </span><span class="text-primary-500 font-normal">Buyer</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">📤 </span><span class="text-primary-500 font-normal">Supplier</span>'))
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
        return Radio::make('status')
            ->label('')
            ->options(self::$statusIconText)
            ->disabled(!User::isUserAuthorizedForOrderStatus())
            ->default('approved');
    }

    /**
     * @return Toggle
     */
    public static function getAttachmentToggle(): Toggle
    {
        return Toggle::make('use_existing_attachments')
            ->label('Use existing attachments')
            ->default(false)
            ->onIcon('heroicon-m-bolt')
            ->offIcon('heroicon-o-no-symbol')
            ->offColor('gray')
            ->columnSpan(2)
            ->live();
    }

    /**
     * @return Select
     */
    public static function getAllProformaInvoices(): Select
    {
        return Select::make('source_proforma_invoice')
            ->label('Select Proforma Invoice (Ref No)')
            ->options(ProformaInvoice::getProformaInvoicesCached())
            ->live()
            ->required()
            ->columnSpan(2)
            ->searchable();
    }

    /**
     * @return Select
     */
    public static function getProformaInvoicesAttachments(): Select
    {
        return Select::make('available_attachments')
            ->label('Select Attachment')
            ->required()
            ->columnSpan(2)
            ->live()
            ->options(function (Get $get, Set $set) {
                $proformaId = $get('source_proforma_invoice');
                if (empty($proformaId)) {
                    return [];
                }

                $attachments = ProformaInvoice::find($proformaId)->attachments;
                if ($attachments->isEmpty()) {
                    return [];
                }

                return $attachments->pluck('name', 'id')
                    ->filter(function ($name) {
                        return !is_null($name);
                    })->toArray();
            });
    }


    /**
     * @return TagsInput
     */
    public static function getPorts(): TagsInput
    {
        return TagsInput::make('extra.port')
            ->label('')
            ->placeholder('Add the requested ports of delivery')
            ->splitKeys(['Tab', 'Enter'])
            ->suggestions(PortOfDelivery::all()->pluck('name')->sort()->toArray())
            ->label(new HtmlString('<span class="grayscale">🏗️  </span><span class="text-primary-500">Port(s)</span> '))
            ->columnSpanFull();
    }

    /**
     * @return TextInput
     */
    public static function getContract(): TextInput
    {
        return TextInput::make('contract_number')
            ->label(fn() => new HtmlString('<span class="grayscale">🗂 </span><span class="text-primary-500 font-normal">CT No.</span>'))
            ->default(fn($operation) => $operation == 'create' ? ProjectNumberGenerator::generate() : null)
            ->placeholder('Enter a contract no, or leave blank for auto-generation')
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state));
    }

    /**
     * @return FileUpload
     */
    public static function getFileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/proforma-invoice')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpan(1);
    }

    /**
     * @return Select
     */
    public static function getAttachmentTitle(): Select
    {
        return Select::make('name')
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Title|Name</span>'))
            ->options(Name::getSortedNamesForModule('ProformaInvoice'))
            ->placeholder('select or make')
            ->requiredWith('file_path')
            ->validationMessages([
                'required_with' => '🚫 The name is required when an attachment is uploaded.',
            ])
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInProformaInvoice)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                Hidden::make('module')
                    ->dehydrateStateUsing(fn($state) => $state ?? 'ProformaInvoice')
                    ->default('ProformaInvoice')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'ProformaInvoice';
                return Name::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new title')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            })
            ->columnSpan(1);
    }
}
