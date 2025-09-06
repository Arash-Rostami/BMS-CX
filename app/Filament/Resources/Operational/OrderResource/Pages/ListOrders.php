<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\SmartCacheManager;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Carbon\Carbon;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
    public $shipmentStatusFilter;
    public ?string $activeTab = '';
    public bool $showTabs;
    public bool $showActionsAhead = true;

    protected $listeners = ['setShipmentStatusFilter', 'refreshPage' => '$refresh', 'updateActiveTab'];

    public function buildBulkDeleteAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->action(fn(Collection $records) => Admin::separateRecordsIntoDeletableAndNonDeletable($records))
            ->deselectRecordsAfterCompletion();
    }

    public function buildDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->successNotification(fn(Model $record) => Admin::send($record))
            ->hidden(fn(?Model $record) => $record?->paymentRequests->isNotEmpty());
    }

    public function buildDocumentSelectClauses(): array
    {
        return collect(Admin::getDynamicDocuments())
            ->keys()
            ->map(fn($key) => DB::raw(sprintf(
                "COUNT(CASE WHEN LOWER(a.name) = '%s' THEN 1 END) as `%s_uploaded`",
                addslashes(strtolower($key)),
                strtolower($key)
            )))
            ->toArray();
    }

    public function buildExportAction(): ExportBulkAction
    {
        return ExportBulkAction::make()->exports([
            ExcelExport::make()
                ->withColumns($this->getExportColumns())
                ->fromTable()
                ->except([...array_map(
                    fn($key) => "extra.docs.$key",
                    array_keys(Admin::getDynamicDocuments())
                )])
                ->modifyQueryUsing(fn($query) => $this->getExportQuery())
                ->withChunkSize(50)
        ]);
    }

    public function buildPaymentAction(): Action
    {
        return Action::make('createPaymentRequest')
            ->authorize('create')
            ->label('Smart Payment')
            ->url(fn($record) => route('filament.admin.resources.payment-requests.create', [
                'id' => $record->id,
                'module' => 'order',
                'type' => 'balance'
            ]))
            ->icon('heroicon-o-credit-card')
            ->color('warning')
            ->openUrlInNewTab();
    }

    public function buildPdfAction(): Action
    {
        return Action::make('pdf')
            ->label('PDF')
            ->color('success')
            ->icon('heroicon-c-inbox-arrow-down')
            ->action(function (Model $record) {
                return response()->streamDownload(function () use ($record) {
                    echo Pdf::loadView('filament.pdfs.order', ['record' => $record])->output();
                }, 'BMS-' . $record->reference_number . '.pdf');
            });
    }

    public function buildReplicateAction(): ReplicateAction
    {
        return ReplicateAction::make()
            ->authorize('create')
            ->color('info')
            ->modalWidth(MaxWidth::Medium)
            ->modalIcon('heroicon-o-clipboard-document-list')
            ->visible(fn($record) => Admin::isPaymentCalculated($record))
            ->beforeReplicaSaved(function (Model $replica) {
                Admin::increasePart($replica);
                Admin::replicateRelatedModels($replica);
            })
            ->after(fn(Model $replica) => Admin::syncOrder($replica))
            ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.orders.edit', ['record' => $replica->id]));
    }

    public function buildTabs(): array
    {
        $counts = Order::getTabCounts();
        $tabConfigs = $this->getTabDefinitions();

        return collect($tabConfigs)->mapWithKeys(function ($config) use ($counts) {
            [$key, $label, $countKey, $icon, $status] = $config;
            return [
                $key => Tab::make($label)
                    ->query(fn($query) => $status ? $query->where('order_status', $status) : $query)
                    ->badge($counts[$countKey] ?? 0)
                    ->icon($icon)
            ];
        })->toArray();
    }


    public function buildToggleTabsAction()
    {
        return Actions\Action::make('Toggle Tabs')
            ->label($this->showTabs ? 'Hide Filter' : 'Show Filters')
            ->tooltip('Toggle Filter Shortcuts')
            ->color($this->showTabs ? 'secondary' : 'primary')
            ->icon($this->showTabs ? 'heroicon-m-eye-slash' : 'heroicon-s-eye')
            ->action(fn() => $this->toggleTabs());
    }

    public function clearTableSort(): void { $this->dispatch('clearTableSort'); }

    public function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->paginated([15, 30])
            ->groupingSettingsInDropdownOnDesktop()
            ->recordClasses(fn(Model $record) => isShadeSelected('order-table'))
            ->filters([Admin::filterSoftDeletes(), Admin::filterNumberOfRecords(), Admin::filterBasedOnQuery()],
                layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->filtersFormColumns(6)
            ->filtersTriggerAction(fn(TableAction $action) => $action->button()->label('')->tooltip('Filter records'))
            ->headerActions($this->getTableTableHeaderActions())
            ->recordUrl(fn(Model $record): string => OrderResource::getUrl(isUserPartner() ? 'view' : 'edit', ['record' => $record]))
            ->actions($this->getTableActions(), position: $this->showActionsAhead ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->bulkActions($this->getBulkActions())
            ->defaultSort('id', 'desc')
            ->groups($this->getTableGroups())
            ->poll('900s')
            ->deferLoading();
    }

    public function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                $this->buildBulkDeleteAction(),
                RestoreBulkAction::make(),
                PrintBulkAction::make(),
                $this->buildExportAction(),
            ]),

        ];
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
                TableObserver::showMissingDataWithRel(-6),
                TableObserver::showCompletionPercentage(true, 6),
                Admin::showCreator(),
                Admin::showTimeStamp()
            ]);
    }

    public function getEagerLoadRelations(): array
    {
        return [
            'attachments',
            'category',
            'doc',
            'grade',
            'logistic.deliveryTerm',
            'logistic.packaging',
            'logistic.portOfDelivery',
            'logistic.shippingLine',
            'orderDetail',
            'party.buyer',
            'party.supplier',
            'paymentRequests.payments',
            'payments',
            'product',
            'proformaInvoice',
            'purchaseStatus',
            'tags',
            'user',
        ];
    }

    public function getExportColumns(): array
    {
        return [
            Column::make('reference_number')->heading('ID'),

            ...collect([
                ['doc.BL_date', 'BL Date'],
                ['doc.declaration_date', 'Declaration Date'],
                ['proforma_date', null],
                ['logistic.extra.loading_startline', 'Loading Start Date'],
                ['logistic.loading_deadline', 'Loading Deadline'],
                ['logistic.extra.etd', 'ETD'],
                ['logistic.extra.eta', 'ETA'],
            ])->map(fn($col) => Column::make($col[0])
                ->heading($col[1] ?? Str::headline($col[0]))
                ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d/m/Y') : null)
            ),

            ...collect(Admin::getDynamicDocuments())->map(fn($label, $key) => Column::make(strtolower($key) . '_uploaded')
                ->heading($label . ' ✓')
                ->formatStateUsing(fn($state) => $state > 0 ? 'Yes' : 'No')
            )->values(),
        ];
    }

    public function getInvisibleTableHeaderActions(): array
    {
        return [
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
                ->visible(!$this->showActionsAhead && !isModernDesign()),

            Action::make('Reset Actions to End')
                ->action('resetActionsToEnd')
                ->color('secondary')
                ->icon('heroicon-o-arrows-right-left')
                ->iconPosition(IconPosition::Before)
                ->label('E')
                ->tooltip('Reset Actions to End')
                ->visible($this->showActionsAhead && !isModernDesign()),

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

    public function getTableGroups(): array
    {
        return [
            Admin::groupByBuyer(), Admin::groupByCategory(), Admin::groupByCurrency(),
            Admin::groupByDeliveryTerm(), Admin::groupByInvoiceNumber(), Admin::groupByPackaging(),
            Admin::groupByPart(), Admin::groupByProduct(), Admin::groupByGrade(),
            Admin::groupByProformaNumber(), Admin::groupByShippingLine(), Admin::groupByStage(),
            Admin::groupByStatus(), Admin::groupBySupplier(), Admin::groupByTags(),
        ];
    }

    public function getTableTableHeaderActions(): array
    {
        return [
            ActionGroup::make(array_merge(
                [
                    $this->buildToggleTabsAction(),
                    PrintAction::make(),
                    ExcelImportAction::make()->color('success'),
                ],
                $this->getInvisibleTableHeaderActions() ?? []
            ))
        ];
    }

    public function getTabs(): array
    {
        if (!$this->showTabs) {
            $this->registerTableRenderHook();
            return [];
        }

        return $this->buildTabs();
    }

    public function mount(): void
    {
        $this->showActionsAhead = $this->showActionsAhead ?? true;
        $this->showTabs = $this->getUserFilterDesign() === 'show';
        $this->dispatch('refreshSortJs');
        $this->dispatch('refreshTabFilters');
    }

    public function moveActionsToStart()
    {
        $this->showActionsAhead = true;
        $this->resetPage();
    }

    public function registerTableRenderHook()
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::HEADER_BEFORE,
            fn(): View => view('filament.resources.order-resource.table-tabs', ['activeTab' => $this->activeTab]),
            scopes: ListOrders::class,
        );
    }

    public function resetActionsToEnd()
    {
        $this->showActionsAhead = false;
        $this->resetPage();
    }

    public function scrollLeft(): void { $this->dispatch('scrollLeft'); }

    public function scrollRight(): void { $this->dispatch('scrollRight'); }

    public function setShipmentStatusFilter($filter)
    {
        $this->shipmentStatusFilter = $filter === 'total' ? null : $filter;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return (getTableDesign() != 'classic')
            ? $this->getModernLayout($this->configureCommonTableSettings($table))
            : $this->getClassicLayout($this->configureCommonTableSettings($table));
    }

    public function toggleFullScreen(): void { $this->dispatch('toggleFullScreen'); }

    public function toggleTabs()
    {
        $this->showTabs = !$this->showTabs;
        $this->resetPage();
    }

    public function updateActiveTab(string $scope = ''): void
    {
        $this->activeTab = $scope;
        $this->dispatch('refreshTabFilters');
    }

    protected function getCachedOrderIds()
    {
        $filters = [
            'activeTab' => $this->activeTab ?: 'all',
            'monthly_data_order' => $this->getTableFilterState('monthly_data_order') ?? 'default',
        ];

        return SmartCacheManager::remember('Order', $filters, (4 * 60), function () {
            $query = self::getOriginalTable();

            if (is_null($this->getTableFilterState('monthly_data_order'))) {
                $query->where('created_at', '>=', now()->subMonths(1)->startOfMonth());
            }

            if ($this->activeTab !== '') {
                $query->where('order_status', $this->activeTab);
            }

            return $query->pluck('id');
        });
    }

    protected function getExportQuery(): ?Builder
    {
        $selectClauses = $this->buildDocumentSelectClauses();

        return Order::query()
            ->select(['orders.*', ...$selectClauses])
            ->leftJoin('attachments as a', 'orders.id', '=', 'a.order_id')
            ->groupBy([
                'orders.id',
                'orders.order_number',
                'orders.reference_number',
                'orders.invoice_number',
                'orders.part',
                'orders.grade_id',
                'orders.proforma_number',
                'orders.proforma_date',
                'orders.order_status',
                'orders.extra',
                'orders.proforma_invoice_id',
                'orders.user_id',
                'orders.purchase_status_id',
                'orders.category_id',
                'orders.product_id',
                'orders.order_detail_id',
                'orders.party_id',
                'orders.logistic_id',
                'orders.doc_id',
                'orders.created_at',
                'orders.updated_at',
                'orders.deleted_at'
            ])
            ->with($this->getEagerLoadRelations());
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->url(fn() => static::getResource()::getUrl('create'))
                ->icon('heroicon-o-sparkles'),
            Actions\Action::make('clear_cache_and_refresh')
                ->label('Refresh')
                ->tooltip('Clear cache and update records')
                ->icon('heroicon-o-arrow-path')
                ->color('secondary')
                ->action(function () {
                    SmartCacheManager::invalidate('Order');
                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('The order data has been refreshed.')
                        ->success()->send();
                }),
            Actions\Action::make('loadMoreMonths')
                ->label('Load More Months')
                ->tooltip('Add to the number of months of requested data')
                ->icon('heroicon-o-plus')
                ->action(function () {
                    $currentMonths = $this->getTableFilterState('monthly_data_order')['months_to_load'] ?? 1;
                    $this->tableFilters['monthly_data_order']['months_to_load'] = $currentMonths + 1;
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                $this->buildReplicateAction(),
                $this->buildDeleteAction(),
                RestoreAction::make(),
                $this->buildPdfAction(),
                $this->buildPaymentAction(),
            ])
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return Order::query()
            ->whereIn('id', $this->getCachedOrderIds())
            ->withCount('paymentRequests')
            ->with($this->getEagerLoadRelations());
    }

    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }

    private function getTabDefinitions(): array
    {
        return [
            [null, 'All', 'total', 'heroicon-o-inbox', null],
            ['Review', 'Review', 'review_count', 'heroicon-o-eye', 'accounting_review'],
            ['Approved', 'Approved', 'approved_count', 'heroicon-o-check-badge', 'accounting_approved'],
            ['Rejected', 'Rejected', 'rejected_count', 'heroicon-o-x-circle', 'accounting_rejected'],
            ['Closed', 'Closed', 'closed_count', 'heroicon-o-check-circle', 'closed'],
        ];
    }

    private function getUserFilterDesign(): string
    {
        return auth()->user()->info['filterDesign'] ?? 'hide';
    }
}
