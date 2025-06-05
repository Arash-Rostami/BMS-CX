<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Pages\ListOrders;
use App\Filament\Resources\Operational\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\Operational\OrderResource\Widgets\StatsOverview;
use App\Models\Order;
use App\Services\AttachmentDeletionService;
use App\Services\OrderPaymentCalculationService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Forms\Components\View as ComponentView;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Infolist;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Operational Data';


    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference_number';


    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pro forma Invoice:')
                            ->schema([
                                Fieldset::make()
                                    ->schema([
                                        Admin::getProformaInvoice(),
                                        Admin::getPart(),
                                    ])->columns(3),
                                Admin::getManualProjectNumber(),
                                Admin::getCategory(),
                                Admin::getProduct(),
                                Fieldset::make()
                                    ->schema([
                                        Admin::getProformaNumber(),
                                        Admin::getProformaDate(),
                                        Admin::getGrade(),
                                    ])->columns(3),
                            ])
                            ->columns(2),
                    ])->columnSpan(3),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status:')
                            ->schema([
                                Admin::getPurchaseStatus(),
                                Admin::getOrderStatus(),
                            ])->columns(2),
                        /*Parties Involved*/
                        Section::make(new HtmlString("Parties: <span class='red'>*</span>"))
                            ->relationship('party')
                            ->label('')
                            ->schema([
                                Admin::getBuyer(),
                                Admin::getSupplier(),
                            ])->columns(2)
                            ->columnSpanFull()
                            ->collapsible(),
                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        /*Order Detailed*/
                        Section::make(new HtmlString("Details: <span class='red'>*</span>"))
                            ->relationship('orderDetail')
                            ->headerActions([
                                Action::make('Compute')
                                    ->label('')
                                    ->tooltip('Compute')
                                    ->icon('heroicon-o-calculator')
                                    ->action(function (Get $get, Set $set, ?Model $record) {
                                        OrderPaymentCalculationService::processPaymentStub($get, $set, $record);
                                    })])
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Fieldset::make()
                                                    ->label(new HtmlString('<span class="text-sm grayscale">💻 Metrics</span>'))
                                                    ->schema([
                                                        Admin::getPercentage(),
                                                        Admin::getCurrency(),
                                                    ])->columns(3),
                                                Forms\Components\Fieldset::make(new HtmlString('<span class="text-sm grayscale">💰 Price (unit)</span>'))
                                                    ->schema([
                                                        Admin::getPrice(),
                                                        Admin::getProvisionalPrice(),
                                                        Admin::getFinalPrice(),
                                                    ])->columns(3),
                                                Forms\Components\Fieldset::make(new HtmlString('<span class="text-sm grayscale">⏲️ Quantity (mt)</span>'))
                                                    ->schema([
                                                        Admin::getQuantity(),
                                                        Admin::getProvisionalQuantity(),
                                                        Admin::getFinalQuantity(),
                                                    ])->columns(3)
                                            ]),
                                    ])->columnSpan(2),
                                Group::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make()
                                            ->label(new HtmlString('⚙️ Pre-payment Options '))
                                            ->schema([
                                                Admin::getManualComputation(),
                                                Admin::getLastOrder(),
                                                Admin::getAllOrders(),
                                                Fieldset::make('🧮 Manual Computation')
                                                    ->schema([
                                                        Admin::getManualInitialPayment(),
                                                        Admin::getManualProvisionalPayment(),
                                                        Admin::getManualFinalPayment(),
                                                    ])
                                                    ->columns(3)
                                                    ->visible(fn(Get $get) => $get('extra.manualComputation')),
                                            ])->columns(3),
                                        Forms\Components\Fieldset::make()
                                            ->label(new HtmlString('⚠ Payment Stub<span class="text-gray-400 text-xs"> - auto computed by BMS</span> '))
                                            ->schema([
                                                // HIDDEN inputs to store date sent by COMPONENT VIEW page
                                                Admin::getPayment(),
                                                Admin::getRemaining(),
                                                Admin::getTotal(),
                                                Admin::getInitialPayment(),
                                                Admin::getInitialTotal(),
                                                Admin::getProvisionalTotal(),
                                                Admin::getFinalTotal(),
                                                Admin::getHiddenBuyingQuantity(),
                                                Admin::getHiddenBuyingPrice(),
                                                Admin::getHiddenPayableQuantity(),
                                                // COMPONENT VIEW page
                                                ComponentView::make('slip')
                                                    ->view('filament.orders.financial-details'),
                                            ])->columns(1),
                                    ])->columnSpan(2),
                            ])
                            ->columnSpanFull()
                            ->columns(4),

                    ])
                    ->columnSpanFull(),

                /*Logistics Info*/
                Section::make('Logistics:')
                    ->relationship('logistic')
                    ->label('')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Admin::getPackaging(),
                                        Admin::getDeliveryTerm(),
                                        Admin::getShippingLine(),
                                        Admin::getPortOfDelivery(),
                                        Admin::getLoadingStartLine(),
                                        Admin::getLoadingDeadline(),
                                        Admin::getETD(),
                                        Admin::getETA(),
                                    ])->columns(3)
                            ])->columnSpan(3),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make(new HtmlString('<span class="text-sm">↻️ Change of Destination</span>'))
                                    ->description(new HtmlString('<span class="text-xs">If so, change the Port of Delivery.</span>'))
                                    ->aside()
                                    ->schema([
                                        Admin::getChangeOfStatus(),
                                    ])->columnSpan(1)
                            ])->columnSpan(1),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Admin::getBookingNumber(),
                                        Admin::getFreeTime(),
                                        Admin::getOceanFreight(),
                                        Admin::getGrossWeight(),
                                        Admin::getNetWeight(),
                                    ])->columns(5),
                                Forms\Components\Group::make()
                                    ->schema([
                                        Admin::getContainerShipping(),
                                        Forms\Components\Section::make()
                                            ->schema([
                                                Admin::getFCL(),
                                                Admin::getFCLType(),
                                                Admin::getNumberOfContainer(),
                                                Admin::getTHC(),
                                            ])
                                            ->columns(4)
                                            ->visible(fn(Get $get) => $get('container_shipping'))
                                    ]),
                            ])->columnSpan(4),
                    ])->columns(4)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),

                /*Documents Involved*/
                Section::make('Shipping Docs:')
                    ->relationship('doc')
                    ->schema([
                        Admin::getDeclarationNumber(),
                        Admin::getDeclarationDate(),
                        Forms\Components\Group::make()
                            ->schema([
                                Section::make(new HtmlString('<span class="text-sm">1️⃣⛴</span>'))
                                    ->schema([
                                        Admin::getVoyageNumber(),
                                        Admin::getBLNumber(),
                                        Admin::getBLDate(),
                                    ])->columns(3),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Section::make(new HtmlString('<span class="text-sm">2️⃣⛴</span>'))
                                    ->schema([
                                        Admin::getVoyageNumberSecondLeg(),
                                        Admin::getBLNumberSecondLeg(),
                                        Admin::getBLDateSecondLeg(),
                                    ])->columns(3),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible(),

                /* Auto-loaded Attachments Section */
                Group::make()
                    ->schema([
                        Admin::getAttachmentToggle(),
                        Section::make()
                            ->schema([
                                Admin::getAllProformaInvoices(),
                                Admin::getProformaInvoicesAttachments(),
                            ])
                            ->columns(4)
                            ->visible(fn($get) => $get('use_existing_attachments')),
                    ])->columnSpanFull(),


                /* Attachments Section */
                Repeater::make('attachments')
                    ->relationship('attachments')
                    ->schema([
                        // General attachment fields
                        Forms\Components\Section::make()
                            ->schema([
                                Hidden::make('id'),
                                Admin::getFileUpload(),
                                Admin::getAttachmentTitle(),
                            ])->columns(2),
                    ])
                    ->columns(4)
                    ->itemLabel('🧷')
                    ->addActionLabel('➕ Add Attachment')
                    ->columnSpanFull()
                    ->collapsible()
                    ->extraItemActions([
                        Action::make('deleteAttachment')
                            ->label('deleteMe')
                            ->visible(fn($operation) => $operation == 'edit')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->modalAlignment(Alignment::Center)
                            ->action(fn(array $arguments, Repeater $component) => AttachmentDeletionService::removeAttachment($component, $arguments['item']))
                            ->modalContent(function (Action $action, array $arguments, Repeater $component, $operation, ?Model $record) {
                                if (str_contains($arguments['item'], 'record')) {
                                    return AttachmentDeletionService::validateAttachmentExists($component, $arguments['item'], $operation, $action, $record);
                                }
                                return new HtmlString("<span>Of course, it is an empty attachment.</span>");
                            })
                            ->modalSubmitActionLabel('Confirm')
                            ->modalWidth(MaxWidth::Medium)
                            ->modalIcon('heroicon-s-exclamation-triangle')
                    ])
                    ->deletable(false)
                    ->visible(fn($get) => !$get('use_existing_attachments'))
                    ->collapsed(),

                /* Tag Section */
                Repeater::make('tags')
                    ->relationship('tags')
                    ->schema([
                        Admin::getTagsInput(),
                        Admin::getModule(),
                    ])
                    ->columns(1)
                    ->itemLabel('🏷️')
                    ->addActionLabel('Add Tag(s)')
                    ->addable(fn(Repeater $component, Get $get) => !(count($get('tags')) == 1))
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ])->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !isSimpleSidebar();
    }


    public static function refreshComponent()
    {
        return self::refreshComponent();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['party', 'orderDetail', 'logistic', 'doc', 'attachments', 'tags'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }


    public static function getRelations(): array
    {
        return [
            Operational\OrderResource\RelationManagers\ProformaInvoiceRelationManagers::class,
//            Operational\OrderResource\RelationManagers\MainPaymentRequestsRelationManager::class,
            Operational\OrderResource\RelationManagers\PaymentRequestsRelationManager::class,
            Operational\OrderResource\RelationManagers\PaymentsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\OrderResource\Pages\ListOrders::route('/'),
            'create' => Operational\OrderResource\Pages\CreateOrder::route('/create'),
            'view' => Operational\OrderResource\Pages\ViewOrder::route('/{record}'),
            'edit' => Operational\OrderResource\Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $navigationItems = [
            Operational\OrderResource\Pages\ViewOrder::class,
            Operational\OrderResource\Pages\EditOrder::class,
        ];

        if (Gate::allows('create', Order::class)) {
            array_unshift($navigationItems, Operational\OrderResource\Pages\CreateOrder::class);
        }

        return $page->generateNavigationItems($navigationItems);
    }


    public static function getAllDocs()
    {
        return Admin::showAllDocs();
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return OrderResource::getUrl('edit', ['record' => $record]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '🛒 ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return (new ViewOrder())->infolist($infolist);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return (new ListOrders())->configureCommonTableSettings($table);
    }

    public static function getModernLayout(Table $table): Table
    {
        return (new ListOrders())->getModernLayout($table);
    }

    public static function getClassicLayout(Table $table)
    {
        return (new ListOrders())->getClassicLayout($table);
    }
}
