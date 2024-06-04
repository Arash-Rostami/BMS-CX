<?php

namespace App\Filament\Resources;

use AnourValar\EloquentSerialize\Tests\Models\Post;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\Operational\OrderResource\Widgets\StatsOverview;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Services\TableObserver;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Collection;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Admin::getOrderRequestNumber(),
                                Admin::getPart(),
                                Admin::getManualInvoiceNumber(),
                                Admin::getCategory(),
                                Admin::getProduct(),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Admin::getProformaNumber(),
                                        Admin::getProformaDate(),
                                        Admin::getGrade(),
                                    ])->columns(3),
                            ])
                            ->columns(2),
                    ]),
                Forms\Components\Group::make()
                    ->schema([
                        Admin::getDocumentsReceived(),
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
                            ->collapsed()
                            ->collapsible(),
                    ]),


                /*Order Detailed*/
                Section::make(new HtmlString("Details: <span class='red'>*</span>"))
                    ->relationship('orderDetail')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Admin::getPercentage(),
                                Admin::getCurrency(),
                                Admin::getPayment(),
                                Admin::getTotal(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make(new HtmlString('<span class="text-sm grayscale">💰 Unit Price</span>'))
                                    ->schema([
                                        Admin::getPrice(),
                                        Admin::getProvisionalPrice(),
                                        Admin::getFinalPrice(),
                                    ])->columns(3)
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make(new HtmlString('<span class="text-sm grayscale">⏲️ Quantity</span>'))
                                    ->schema([
                                        Admin::getQuantity(),
                                        Admin::getProvisionalQuantity(),
                                        Admin::getFinalQuantity(),
                                    ])->columns(3)
                            ])
                    ])
                    ->columns(2)
                    ->collapsible(),

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
                                        Admin::getOcceanFreight(),
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

                /*Additional Attachments*/
                Repeater::make('attachments')
                    ->relationship('attachments')
                    ->label('Attachments')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Admin::getFileUpload()
                                    ])
                            ])->columnSpan(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Admin::getAttachmentTitle(),
                                    ])
                            ])->columnSpan(2)
                    ])->columns(4)
                    ->itemLabel('Attachments:')
                    ->addActionLabel('➕')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * @throws \Exception
     */
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
            Operational\OrderResource\RelationManagers\OrderRequestRelationManager::class,
            Operational\OrderResource\RelationManagers\PaymentRequestsRelationManager::class,
            Operational\OrderResource\RelationManagers\PaymentsRelationManager::class,
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
            ->filters([Admin::filterOrderStatus(), Admin::filterCreatedAt(), Admin::filterSoftDeletes()])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(fn(Model $record) => Admin::send($record)),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $selectedRecords) {
                            $selectedRecords->each->delete();
                            $selectedRecords->each(fn(Model $selectedRecord) => Admin::send($selectedRecord));
                        }),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                ])
            ])
            ->defaultSort('created_at', 'desc')
            ->poll(60)
            ->groups([
                Admin::groupByBuyer(),
                Admin::groupByCategory(),
                Admin::groupByCurrency(),
                Admin::groupByDeliveryTerm(),
                Admin::groupByInvoiceNumber(),
                Admin::groupByPackaging(),
                Admin::groupByPart(),
                Admin::groupByProduct(),
                Admin::groupByProformaNumber(),
                Admin::groupByShippingLine(),
                Admin::groupByStage(),
                Admin::groupByStatus(),
                Admin::groupBySupplier(),
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
                                Admin::showProformaNumber(),
                                Admin::showProformaDate(),
                                Admin::showPercentage(),
                                Admin::showInvoiceNumber(),
                                Admin::showOrderPart(),
                            ]),
                            Split::make([
                                Admin::showCategory(),
                                Admin::showProduct(),
                                Admin::showGrade(),
                                Admin::showPurchaseStatus(),
                                Admin::showOrderStatus(),

                            ]),
                            Split::make([
                                Stack::make([
                                    Admin::showOrderNumber(),
                                ]),
//                                TableObserver::showMissingDataWithRel(-12),
                                Admin::showPaymentRequests(),
                                Admin::showPayments(),

                            ]),
                        ])->space(2),
                    ])
                ])->columnSpanFull(),
                Admin::showUpdatedAt(),
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                TableObserver::showMissingDataWithRel(-12),
                Admin::showProformaNumber(),
                Admin::showProformaDate(),
                Admin::showInvoiceNumber(),
                Admin::showOrderPart(),
                Admin::showOrderStatus(),
                Admin::showCategory(),
                Admin::showProduct(),
                Admin::showGrade(),
                Admin::showPurchaseStatus(),
                Admin::showSupplier(),
                Admin::showBuyer(),
                Admin::showQuantities(),
                Admin::showPrices(),
                Admin::showPercentage(),
                Admin::showDeliveryTerm(),
                Admin::showPackaging(),
                Admin::showShippingLine(),
                Admin::showPortOfDelivery(),
                Admin::showChangeOfDestination(),
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
                Admin::showGrossWeight(),
                Admin::showNetWeight(),
                Admin::showBookingNumber(),
                Admin::showDeclarationNumber(),
                Admin::showDeclarationDate(),
                Admin::showVoyageNumber(),
                Admin::showBLNumber(),
                Admin::showBLDate(),
                Admin::showVoyageNumberLegTwo(),
                Admin::showBLNumberLegTwo(),
                Admin::showBLDateLegTwo(),
                Admin::showOrderNumber(),
            ])
            ->striped();
    }
}
