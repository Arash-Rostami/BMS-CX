<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Notifications\Notification;


class ListOrders extends ListRecords
{
    public $shipmentStatusFilter;

    public bool $showTabs;


    protected $listeners = ['setShipmentStatusFilter', 'refreshPage' => '$refresh'];


    protected static string $resource = OrderResource::class;

    public bool $showActionsAhead = true;

    public function mount(): void
    {
        $this->showActionsAhead = $this->showActionsAhead ?? true;
        $this->showTabs = (auth()->user()->info['filterDesign'] ?? 'hide') == 'show';
        $this->dispatch('refreshSortJs');
    }

    public function toggleTabs()
    {
        $this->showTabs = !$this->showTabs;
        $this->dispatch('refreshPage');
    }

    public function moveActionsToStart()
    {
        $this->showActionsAhead = true;
        $this->resetPage();
    }

    public function resetActionsToEnd()
    {
        $this->showActionsAhead = false;
        $this->resetPage();
    }

    public function scrollLeft()
    {
        $this->dispatch('scrollLeft');
    }

    public function scrollRight()
    {
        $this->dispatch('scrollRight');
    }

    public function toggleFullScreen()
    {
        $this->dispatch('toggleFullScreen');
    }

    public function setShipmentStatusFilter($filter)
    {
        $this->shipmentStatusFilter = $filter === 'total' ? null : $filter;
        $this->resetPage();
    }

    public function clearTableSort()
    {
        $this->dispatch('clearTableSort');
    }


    public function getTabs(): array
    {
        if (!$this->showTabs) {
            return [];
        }


        $counts = Order::getTabCounts();

        return [
            null => Tab::make('All')
                ->query(fn($query) => $query)
                ->badge($counts['total'] ?? 0)
                ->icon('heroicon-o-inbox'),

            'Pending' => Tab::make('Pending')
                ->query(fn($query) => $query->where('purchase_status_id', 1))
                ->badge($counts['pending_count'] ?? 0)
                ->icon('heroicon-o-clock'),

            'In Transit' => Tab::make('In Transit')
                ->query(fn($query) => $query->where('purchase_status_id', 3))
                ->badge($counts['in_transit_count'] ?? 0)
                ->icon('heroicon-o-truck'),

            'Delivered' => Tab::make('Delivered')
                ->query(fn($query) => $query->where('purchase_status_id', 5))
                ->badge($counts['delivered_count'] ?? 0)
                ->icon('heroicon-o-check-circle'),

            'Shipped' => Tab::make('Shipped')
                ->query(fn($query) => $query->where('purchase_status_id', 6))
                ->badge($counts['shipped_count'] ?? 0)
                ->icon('heroicon-o-paper-airplane'),

            'Released' => Tab::make('Released')
                ->query(fn($query) => $query->where('purchase_status_id', 2))
                ->badge($counts['released_count'] ?? 0)
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }


    public function getTableHeaderActions(): array
    {
        $design = getTableDesign() == 'modern';

        $actions = [
            Action::make('Refresh Sorting')
                ->label('Reset')
                ->tooltip('Reset Column Orders')
                ->color('primary')
                ->icon('heroicon-m-receipt-refund')
                ->action('clearTableSort'),

            Action::make('Move Actions to Start')
                ->action('moveActionsToStart')
                ->color('primary')
                ->icon('heroicon-o-arrows-right-left')
                ->iconPosition(IconPosition::After)
                ->label('S')
                ->tooltip('Move Actions to Start')
                ->visible(!$this->showActionsAhead && !$design),

            Action::make('Reset Actions to End')
                ->action('resetActionsToEnd')
                ->color('secondary')
                ->icon('heroicon-o-arrows-right-left')
                ->iconPosition(IconPosition::Before)
                ->label('E')
                ->tooltip('Reset Actions to End')
                ->visible($this->showActionsAhead && !$design),

            Action::make('Scroll Left')
                ->label('Scroll')
                ->tooltip('Scroll Left')
                ->color('primary')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->iconPosition(IconPosition::Before)
                ->action('scrollLeft'),

            Action::make('Scroll Right')
                ->label('Scroll')
                ->tooltip('Scroll Right')
                ->color('primary')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->iconPosition(IconPosition::After)
                ->action('scrollRight'),

            Action::make('Full Screen')
                ->label('Go')
                ->tooltip('Go Fullscreen')
                ->color('primary')
                ->icon('heroicon-s-arrows-pointing-out')
                ->action('toggleFullScreen'),
        ];

        if ($design) {
            return [ActionGroup::make($actions)];
        }

        return $actions;
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),
            ActionGroup::make([
                Actions\Action::make('Toggle Tabs')
                    ->label($this->showTabs ? 'Hide Shortcuts' : 'Show Shortcuts')
                    ->tooltip('Toggle Filter Shortcuts')
                    ->color($this->showTabs ? 'secondary' : 'primary')
                    ->icon($this->showTabs ? 'heroicon-m-eye-slash' : 'heroicon-s-eye')
                    ->action('toggleTabs'),
                PrintAction::make(),
                ExcelImportAction::make()
                    ->color("success"),
            ])
        ];
    }


    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }


    protected function getTableQuery(): Builder
    {
        //        if ($this->shipmentStatusFilter) {
//            $query->where('purchase_status_id', $this->shipmentStatusFilter);
//        }

        return self::getOriginalTable();
    }


    public function table(Table $table): Table
    {
        $table = $this->configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? $this->getModernLayout($table)
            : $this->getClassicLayout($table);
    }

    public function configureCommonTableSettings(Table $table): Table
    {
        return $table
//            ->defaultGroup('invoice_number')
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->paginated([20, 30, 40])
            ->groupingSettingsInDropdownOnDesktop()
            ->recordClasses(fn(Model $record) => isShadeSelected('order-table'))
            ->filters([
                Admin::filterSoftDeletes(),
                Admin::filterBasedOnQuery()
//                Admin::filterOrderStatus(), Admin::filterCreatedAt(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(fn(TableAction $action) => $action->button()->label('')->tooltip('Filter records'))
            ->headerActions($this->getTableHeaderActions())
            ->recordUrl(fn(Model $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->color('info')
                        ->modalWidth(MaxWidth::Medium)
                        ->modalIcon('heroicon-o-clipboard-document-list')
//                        ->record(fn(Model $record) => $record)
                        ->visible(fn($record) => Admin::isPaymentCalculated($record))
                        ->beforeReplicaSaved(function (Model $replica) {
                            Admin::increasePart($replica);
                            Admin::replicateRelatedModels($replica);
                        })
                        ->after(fn(Model $replica) => Admin::syncOrder($replica))
                        ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.orders.edit', ['record' => $replica->id,])),
                    DeleteAction::make()
                        ->successNotification(fn(Model $record) => Admin::send($record))
                        ->hidden(fn(?Model $record) => $record ? $record->paymentRequests->isNotEmpty() : false),
                    RestoreAction::make(),
                    Action::make('pdf')
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
                    Action::make('createPaymentRequest')
                        ->label('Smart Payment')
                        ->url(fn($record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'order']))
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->openUrlInNewTab(),
                ])
            ], position: $this->showActionsAhead ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn(Collection $records) => Admin::separateRecordsIntoDeletableAndNonDeletable($records))
                        ->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                    PrintBulkAction::make(),
                ])
            ])
            ->defaultSort('id', 'desc')
//            ->deferLoading()
            ->poll('240s')
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


    public function getModernLayout(Table $table): Table
    {
        $docChunks = array_chunk(Admin::showAllDocs(), 10);
        $splitDocs = [];
        foreach ($docChunks as $chunk) {
            $splitDocs[] = Split::make($chunk)->columnSpanFull(true);
        }

        return $table->columns([
            Stack::make([
                Split::make([
                    Admin::showReferenceNumber(),
                    Admin::showProformaNumber(),
                    Admin::showOrderPart(),
                    Admin::showSupplier(),
                    Admin::showAllPayments(),
                ]),
            ])->space(3),
            Split::make([
                Stack::make([
                    Split::make([
                        Admin::showBookingNumber(),
                        Admin::showBLNumber(),
                        Admin::showPortOfDelivery(),
                        Admin::showPaymentRequests(),
                        Admin::showPayments(),
                    ])->columnSpanFull(true),
                    Stack::make($splitDocs),
                ]),
            ])->collapsible(),
            Split::make([
                Admin::showProjectNumber(),
            ]),
        ]);
    }


    public function getClassicLayout(Table $table): Table
    {
        $showAllDocs = Admin::showAllDocs();
        return $table
            ->columns([
                Admin::showReferenceNumber(),
                Admin::showOrderStatus(),
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
                TableObserver::showMissingDataWithRel(-12),
            ]);
    }
}
