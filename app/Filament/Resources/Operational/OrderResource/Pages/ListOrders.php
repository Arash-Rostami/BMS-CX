<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Carbon\Carbon;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentView;
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
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
    public $shipmentStatusFilter;
    public bool $showTabs;
    public ?string $activeTab = '';
    public bool $showActionsAhead = true;
    protected $listeners = ['setShipmentStatusFilter', 'refreshPage' => '$refresh', 'updateActiveTab'];

    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }

    public function mount(): void
    {
        $this->showActionsAhead = $this->showActionsAhead ?? true;
        $this->showTabs = (auth()->user()->info['filterDesign'] ?? 'hide') == 'show';
        $this->dispatch('refreshSortJs');
        $this->dispatch('refreshTabFilters');
    }

    public function updateActiveTab(string $scope = ''): void
    {
        $this->activeTab = $scope;
        $this->dispatch('refreshTabFilters');
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
            $this->registerTableRenderHook();
            return [];
        }

        $counts = Order::getTabCounts();

        return [
            null => Tab::make('All')
                ->query(fn($query) => $query)
                ->badge($counts['total'] ?? 0)
                ->icon('heroicon-o-inbox'),

            'Review' => Tab::make('Review')
                ->query(fn($query) => $query->where('order_status', 'accounting_review'))
                ->badge($counts['review_count'] ?? 0)
                ->icon('heroicon-o-eye'),

            'Approved' => Tab::make('Approved')
                ->query(fn($query) => $query->where('order_status', 'accounting_approved'))
                ->badge($counts['approved_count'] ?? 0)
                ->icon('heroicon-o-check-badge'),

            'Rejected' => Tab::make('Rejected')
                ->query(fn($query) => $query->where('order_status', 'accounting_rejected'))
                ->badge($counts['rejected_count'] ?? 0)
                ->icon('heroicon-o-x-circle'),

            'Closed' => Tab::make('Closed')
                ->query(fn($query) => $query->where('order_status', 'closed'))
                ->badge($counts['closed_count'] ?? 0)
                ->icon('heroicon-o-check-circle'),
        ];
    }

    private function registerTableRenderHook()
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::HEADER_BEFORE,
            fn(): View => view('filament.resources.order-resource.table-tabs', ['activeTab' => $this->activeTab]),
            scopes: ListOrders::class,
        );
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
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->paginated([20, 30, 40])
            ->groupingSettingsInDropdownOnDesktop()
            ->recordClasses(fn(Model $record) => isShadeSelected('order-table'))
            ->filters([
                Admin::filterSoftDeletes(),
                Admin::filterBasedOnQuery()
            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->filtersFormColumns(6)
            ->filtersTriggerAction(fn(TableAction $action) => $action->button()->label('')->tooltip('Filter records'))
            ->headerActions($this->getTableHeaderActions())
            ->recordUrl(fn(Model $record): string => OrderResource::getUrl(isUserPartner() ? 'view' : 'edit', ['record' => $record]))
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->authorize('create')
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
                                echo Pdf::loadView('filament.pdfs.paymentRequest', ['record' => $record])->output();
                            }, 'BMS-' . $record->reference_number . '.pdf');
                        }),
                    Action::make('createPaymentRequest')
                        ->authorize('create')
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
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->except(array_map(fn($key) => "extra.docs_received.$key", array_keys(Admin::getDynamicDocuments())))
                            ->withColumns([
                                Column::make('reference_number')->heading('ID'),
                                Column::make('doc.BL_date')
                                    ->heading('BL Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('doc.declaration_date')
                                    ->heading('Declaration Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('proforma_date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('logistic.extra.loading_startline')
                                    ->heading('Loading Start Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('logistic.loading_deadline')
                                    ->heading('Loading Deadline')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('logistic.extra.etd')
                                    ->heading('ETD')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('logistic.extra.eta')
                                    ->heading('ETA')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                            ])
                    ]),
                    PrintBulkAction::make(),
                ])
            ])
            ->defaultSort('id', 'desc')
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
        $splitDocs = array_map(
            fn($chunk) => Split::make($chunk)->columnSpanFull(),
            $docChunks
        );

        return $table->columns([
            Stack::make([
                Split::make([
                    Admin::showReferenceNumber(),
                    Admin::showProformaNumber(),
                    Admin::showOrderPart(),
                    Admin::showSupplier(),
                    Admin::showAllPayments(),
                ]),
            ])->space(0),

            Split::make([
                Stack::make([
                    Split::make([
                        Admin::showPurchaseStatus(),
                        Admin::showBookingNumber(),
                        Admin::showBLNumber(),
                        Admin::showPortOfDelivery(),
                        Admin::showPaymentRequests(),
                        Admin::showPayments(),
                    ])->columnSpanFull(),

                    Stack::make($splitDocs)
                        ->space(1),
                ])->space(3),
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
                Admin::showPurchaseStatus(),
                Admin::showProjectNumber(),
                Admin::showSupplier(),
                Admin::showProformaNumber(),
                Admin::showProformaDate(),
                Admin::showProduct(),
                Admin::showGrade(),
                Admin::showOrderPart(),
                Admin::showQuantities(),
                Admin::showProvisionalQuantity(),
                Admin::showFinalQuantity(),
                Admin::showAllPayments(),
                Admin::showProvisionalPrice(),
                Admin::showFinalPrice(),
                Admin::showPortOfDelivery(),
                Admin::showLeadTime(),
                Admin::showBookingNumber(),
                Admin::showBLNumber(),
                Admin::showBLDate(),
                Admin::showVoyageNumber(),
                Admin::showGrossWeight(),
                Admin::showNetWeight(),
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),

            ActionGroup::make(array_merge(
                [
                    Actions\Action::make('Toggle Tabs')
                        ->label($this->showTabs ? 'Hide Shortcuts' : 'Show Shortcuts')
                        ->tooltip('Toggle Filter Shortcuts')
                        ->color($this->showTabs ? 'secondary' : 'primary')
                        ->icon($this->showTabs ? 'heroicon-m-eye-slash' : 'heroicon-s-eye')
                        ->action('toggleTabs'),
                    PrintAction::make(),
                    ExcelImportAction::make()
                        ->color("success"),
                ],
                $this->getInvisibleTableHeaderActions() ?? []
            ))
        ];
    }

    public function getInvisibleTableHeaderActions(): array
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

//        if ($design) {
//            return [ActionGroup::make($actions)];
//        }

        return $actions;
    }

    protected function getTableQuery(): ?Builder
    {
        $query = Order::query();

        if ($this->activeTab !== '') {
            $query->where('order_status', $this->activeTab);
        }

        return $query;
    }
}
