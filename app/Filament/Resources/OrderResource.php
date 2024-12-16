<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Widgets\StatsOverview;
use App\Models\Order;
use App\Services\AttachmentDeletionService;
use App\Services\OrderPaymentCalculationService;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Forms\Components\View as ComponentView;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;


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

    public static function refreshComponent()
    {
        return self::refreshComponent();
    }


    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);

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
            Operational\OrderResource\RelationManagers\MainPaymentRequestsRelationManager::class,
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
        return $page->generateNavigationItems([
            Operational\OrderResource\Pages\CreateOrder::class,
            Operational\OrderResource\Pages\ViewOrder::class,
            Operational\OrderResource\Pages\EditOrder::class,
        ]);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->defaultGroup('invoice_number')
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->paginated([10, 15, 20])
            ->groupingSettingsInDropdownOnDesktop()
            ->filters([
                Admin::filterSoftDeletes(),
                Admin::filterBasedOnQuery()
//                Admin::filterOrderStatus(), Admin::filterCreatedAt(),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(fn(TableAction $action) => $action->button()->label('')->tooltip('Filter records'))
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()
                    ->color('info')
                    ->modalWidth(MaxWidth::Medium)
                    ->modalIcon('heroicon-o-clipboard-document-list')
                    ->record(fn(Model $record) => $record)
                    ->visible(fn($record) => Admin::isPaymentCalculated($record))
                    ->beforeReplicaSaved(function (Model $replica) {
                        Admin::increasePart($replica);
                        Admin::replicateRelatedModels($replica);
                    })
                    ->after(fn(Model $replica) => Admin::syncOrder($replica))
                    ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.orders.edit', ['record' => $replica->id,])),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(fn(Model $record) => Admin::send($record))
                    ->hidden(fn(?Model $record) => $record ? $record->paymentRequests->isNotEmpty() : false),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-c-inbox-arrow-down')
                    ->action(function (Model $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadHtml(view('filament.pdfs.order', ['record' => $record])
                                ->render())
                                ->stream();
                        }, 'BMS-' . $record->reference_number . '.pdf');
                    }),
                Tables\Actions\Action::make('createPaymentRequest')
                    ->label('Smart Payment')
                    ->url(fn($record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'order']))
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn(Collection $records) => Admin::separateRecordsIntoDeletableAndNonDeletable($records))
                        ->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                    PrintBulkAction::make(),
                ])
            ])
            ->defaultSort('part', 'asc')
            ->deferLoading()
            ->poll('120s')
            ->groups([
                Admin::groupByBuyer(),
                Admin::groupByCategory(),
                Admin::groupByCurrency(),
                Admin::groupByDeliveryTerm(),
                Admin::groupByInvoiceNumber(),
                Admin::groupByPackaging(),
                Admin::groupByPart(),
                Admin::groupByProduct(),
                Admin::groupByGrade(),
                Admin::groupByProformaNumber(),
                Admin::groupByShippingLine(),
                Admin::groupByStage(),
                Admin::groupByStatus(),
                Admin::groupBySupplier(),
                Admin::groupByTags(),
            ]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Stack::make([
                            Split::make([
                                Admin::showReferenceNumber(),
                                Admin::showProjectNumber(),
                                Admin::showOrderPart(),
                                Admin::showSupplier(),
                                Admin::showBuyer(),
                            ]),
                            Split::make([
                                Admin::showProformaNumber(),
                                Admin::showProformaDate(),
                                Admin::showAllPayments(),
                            ]),
                            Split::make([
                                Admin::showProduct(),
                                Admin::showGrade(),
                                Admin::showPurchaseStatus(),
                                Admin::showOrderStatus(),
                            ]),
                            Split::make([
                                Admin::showBookingNumber(),
                                Admin::showBLNumber(),
//                                TableObserver::showMissingDataWithRel(-12),
                            ]),
                            Split::make([
                                Admin::showPaymentRequests(),
                                Admin::showPayments(),
                            ]),
                        ])->space(2),
                    ])
                ])->columnSpanFull(),
//                Split::make([
//                    View::make('filament.orders.collapsible-row-content')
//                ]),
                Split::make([
                    Admin::showOrderNumber(),
                ]),
                Admin::showUpdatedAt(),
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        $showAllDocs = Admin::showAllDocs();
        return $table
            ->columns([
                Admin::showReferenceNumber(),
                Admin::showProjectNumber(),
                Admin::showSupplier(),
                Admin::showProformaNumber(),
                Admin::showProformaDate(),
                Admin::showProduct(),
                Admin::showGrade(),
                Admin::showOrderPart(),
                Admin::showQuantities(),
                Admin::showAllPayments(),
                Admin::showPortOfDelivery(),
                Admin::showBookingNumber(),
                Admin::showBLNumber(),
                Admin::showBLDate(),
                Admin::showVoyageNumber(),
                Admin::showGrossWeight(),
                Admin::showNetWeight(),
                Admin::showPurchaseStatus(),
                Admin::showCategory(),
                Admin::showBuyer(),
                Admin::showDeliveryTerm(),
                Admin::showPackaging(),
                Admin::showShippingLine(),
                Admin::showLoadingStartline(),
                Admin::showLoadingDeadline(),
                Admin::showEtd(),
                Admin::showEta(),
                Admin::showFCL(),
                Admin::showFCLType(),
                Admin::showNumberOfContainers(),
                Admin::showOceanFreight(),
                Admin::showTHC(),
                Admin::showFreeTimePOD(),
                Admin::showDeclarationNumber(),
                Admin::showDeclarationDate(),
                Admin::showBLNumberLegTwo(),
                Admin::showBLDateLegTwo(),
                Admin::showVoyageNumberLegTwo(),
                Admin::showOrderNumber(),
                Admin::showChangeOfDestination(),
                ...$showAllDocs,
                Admin::showCreator(),
                Admin::showOrderStatus(),
                TableObserver::showMissingDataWithRel(-12),
            ]);
    }

    public static function getAllDocs()
    {
        return Admin::showAllDocs();
    }

//    public static function getTableQuery()
//    {
//        return parent::getTableQuery()->orderBy('part', 'asc')->orderBy('created_at', 'desc');
//    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return OrderResource::getUrl('edit', ['record' => $record]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '🛒 ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }
}
