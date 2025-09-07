<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\ProformaInvoice;
use App\Services\SmartCacheManager;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Carbon\Carbon;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Actions\Action;
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


class ListProformaInvoices extends ListRecords
{
    private const SPECIFIC_CATEGORY_IDS = ['Mineral' => 1, 'Polymers' => 2, 'Chemicals' => 3, 'Petro' => 4];
    protected static string $resource = ProformaInvoiceResource::class;
    public bool $showActionsAhead = true;

    public bool $showTabs;
    public ?string $activeTab = '';


    protected $listeners = [
        'refreshPage' => '$refresh',
        'updateActiveTab'
    ];

    public function clearTableSort()
    {
        $this->dispatch('clearTableSort');
    }

    public function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([
                Admin::filterNumberOfRecords(),
                Admin::filterCategory(),
                Admin::filterProduct(),
                Admin::filterGrade(),
                Admin::filterSupplier(),
                Admin::filterCreator(),
                Admin::filterPart(),
                Admin::filterStatus(),
                Admin::filterProforma(),
                AdminOrder::filterSoftDeletes(),
                Admin::filterVerified(),
                Admin::filterTelexNeeded(),
            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->filtersFormColumns(4)
            ->recordClasses(fn(Model $record) => ($record->status == 'rejected')
                ? 'bg-cancelled'
                : isShadeSelected('proforma-table'))
            ->headerActions($this->getTableHeaderActions())
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->successNotification(fn(Model $record) => Admin::send($record))
                        ->hidden(fn(?Model $record) => $record && ($record->activeApprovedPaymentRequests->isNotEmpty() || $record->activeOrders->isNotEmpty())),
                    RestoreAction::make(),
                    Action::make('pdf')
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-c-inbox-arrow-down')
                        ->action(function (Model $record) {
                            return response()->streamDownload(function () use ($record) {
                                echo Pdf::loadView('filament.pdfs.proformaInvoice', ['record' => $record])->output();
                            }, 'BMS-' . $record->reference_number . '.pdf');
                        }),
                    Action::make('createPaymentRequest')
                        ->authorize('create')
                        ->label('Smart Payment')
                        ->url(fn($record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'proforma-invoice']))
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->openUrlInNewTab(),
                    ReplicateAction::make()
                        ->authorize('create')
                        ->color('info')
                        ->modalWidth(MaxWidth::Medium)
                        ->modalIcon('heroicon-o-clipboard-document-list')
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['user_id'] = auth()->id();
                            return $data;
                        })
                        ->after(fn(Model $replica) => Admin::syncProformaInvoice($replica))
                        ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.proforma-invoices.edit', ['record' => $replica->id,])),
                ]),

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
                            ->withColumns([
                                Column::make('reference_number')->heading('ID'),
                                Column::make('proforma_date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
                            ])
                    ]),
                    PrintBulkAction::make(),
                ])
            ])
            ->paginated([15, 30])
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultSort('reference_number', 'desc')
            ->groups([
                Admin::groupProformaInvoiceRecords(),
                Admin::groupProformaDateRecords(),
                Admin::groupPartRecords(),
                Admin::groupCategoryRecords(),
                Admin::groupProductRecords(),
                Admin::groupBuyerRecords(),
                Admin::groupSupplierRecords(),
                Admin::groupContractRecords(),
                Admin::groupStatusRecords(),
            ])
            ->poll('900s');
    }

    public function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showID(),
                Admin::showStatus(),
                Admin::showProformaNumber(),
                Admin::showProformaDate(),
                Admin::showBuyer(),
                Admin::showSupplier(),
                Admin::showCategory(),
                Admin::showProduct(),
                Admin::showGrade(),
                Admin::showPrice(),
                Admin::showQuantity(),
                Admin::showPercentage(),
                Admin::showTotal(),
                Admin::showShipmentPart(),
                Admin::showVerifiable(),
                Admin::showContractName(),
                Admin::showCreator(),
                Admin::showAssignedTo(),
                TableObserver::showMissingData(),
                Admin::showTimeStamp(),
            ])->striped();
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
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showID(),
                        Admin::showProformaNumber(),
                        Admin::showProduct(),
                        Admin::showGrade(),
                        Admin::showSupplier(),
                        Admin::showVerifiable(),
                        Admin::showContractName(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showBuyer(),
                        Admin::showSupplier(),
                        Admin::showPrice(),
                        Admin::showQuantity(),
                        Admin::showPercentage(),
                        Admin::showTotal(),
                    ])->columnSpanFull(true),
                ])->collapsible(),
            ]);
    }


    public function getTabs(): array
    {
        if (!$this->showTabs) {
            $this->registerTableRenderHook();
            return [];
        }

        $counts = ProformaInvoice::getTabCounts();

        $baseTabs = collect([
            null => [
                'label' => 'All',
                'query' => fn(Builder $query) => $query,
                'badge' => $counts['total'] ?? 0,
                'icon' => 'heroicon-o-inbox',
            ],
        ]);

        $categoryTabs = collect([
            'Mineral' => ['label' => 'Mineral', 'query' => fn(Builder $q) => $q->where('category_id', 1), 'badge' => $counts['mineral_count'] ?? 0, 'icon' => 'heroicon-o-cube'],
            'Polymers' => ['label' => 'Polymers', 'query' => fn(Builder $q) => $q->where('category_id', 2), 'badge' => $counts['polymers_count'] ?? 0, 'icon' => 'heroicon-m-circle-stack'],
            'Chemicals' => ['label' => 'Chemicals', 'query' => fn(Builder $q) => $q->where('category_id', 3), 'badge' => $counts['chemicals_count'] ?? 0, 'icon' => 'heroicon-o-beaker'],
            'Petro' => ['label' => 'Petro', 'query' => fn(Builder $q) => $q->where('category_id', 4), 'badge' => $counts['petro_count'] ?? 0, 'icon' => 'heroicon-s-fire'],
        ]);

        $buyerTabs = collect([
            'Persore' => ['label' => 'Persore', 'query' => fn(Builder $q) => $q->where('buyer_id', 5), 'badge' => $counts['persore_count'] ?? 0, 'icon' => 'heroicon-o-user-group'],
            'Paidon' => ['label' => 'Paidon', 'query' => fn(Builder $q) => $q->where('buyer_id', 2), 'badge' => $counts['paidon_count'] ?? 0, 'icon' => 'heroicon-o-user-group'],
            'Zhuo' => ['label' => 'Zhuo', 'query' => fn(Builder $q) => $q->where('buyer_id', 3), 'badge' => $counts['zhuo_count'] ?? 0, 'icon' => 'heroicon-o-user-group'],
            'Solsun' => ['label' => 'Solsun', 'query' => fn(Builder $q) => $q->where('buyer_id', 4), 'badge' => $counts['solsun_count'] ?? 0, 'icon' => 'heroicon-o-user-group'],
        ]);

        $statusTabs = collect([
            'Approved' => ['label' => 'Approved', 'query' => fn(Builder $q) => $q->where('status', 'approved'), 'badge' => $counts['approved_count'] ?? 0, 'icon' => 'heroicon-o-check-circle'],
            'Rejected' => ['label' => 'Rejected/Cancelled', 'query' => fn(Builder $q) => $q->where('status', 'rejected'), 'badge' => $counts['rejected_count'] ?? 0, 'icon' => 'heroicon-o-x-circle'],
            'Completed' => ['label' => 'Completed', 'query' => fn(Builder $q) => $q->where('status', 'fulfilled'), 'badge' => $counts['fulfilled_count'] ?? 0, 'icon' => 'heroicon-s-check-circle'],
        ]);

        return $baseTabs
            ->merge($categoryTabs)
            ->merge($buyerTabs)
            ->merge($statusTabs)
            ->map(fn(array $config): Tab => Tab::make($config['label'])
                ->query($config['query'])
                ->badge($config['badge'])
                ->icon($config['icon'])
            )
            ->all();
    }

    public function mount(): void
    {
        $this->showActionsAhead = $this->showActionsAhead ?? true;
        $this->showTabs = (auth()->user()->info['filterDesign'] ?? 'hide') == 'show';
        $this->dispatch('refreshSortJs');
        $this->dispatch('refreshTabFilters');
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

    public function table(Table $table): Table
    {
        $table = $this->configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? $this->getModernLayout($table)
            : $this->getClassicLayout($table);
    }

    public function toggleFullScreen()
    {
        $this->dispatch('toggleFullScreen');
    }

    public function toggleTabs()
    {
        $this->showTabs = !$this->showTabs;
        $this->dispatch('refreshPage');
    }

    public function updateActiveTab(string $scope = ''): void
    {
        $this->activeTab = $scope;
        $this->dispatch('refreshTabFilters');
    }

    protected function getCachedProformaInvoiceIds()
    {
        $filters = [
            'activeTab' => $this->activeTab ?: 'all',
            'monthly_data_proforma' => $this->getTableFilterState('monthly_data_proforma') ?? 'default',
        ];

        return SmartCacheManager::remember('ProformaInvoice', $filters, (4 * 60), function () {
            $query = self::getOriginalTable();


            if (is_null($this->getTableFilterState('monthly_data_proforma'))) {
                $query->where('created_at', '>=', now()->subMonths(2)->startOfMonth());
            }

            if ($this->activeTab !== '') {
                if (array_key_exists($this->activeTab, self::SPECIFIC_CATEGORY_IDS)) {
                    $query->where('category_id', self::SPECIFIC_CATEGORY_IDS[$this->activeTab]);
                }
            }

            return $query->pluck('id');
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),
            Actions\Action::make('clear_cache_and_refresh')
                ->label('Refresh')
                ->tooltip('Clear cache and update records')
                ->icon('heroicon-o-arrow-path')
                ->color('secondary')
                ->action(function () {
                    SmartCacheManager::invalidate('ProformaInvoice');
                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('The proforma invoice data has been refreshed.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('loadMoreMonths')
                ->label('Load More Months')
                ->tooltip('Add to the number of months of requested data')
                ->icon('heroicon-o-plus')
                ->action(function () {
                    $currentMonths = $this->getTableFilterState('monthly_data_proforma')['months_to_load'] ?? 2;
                    $this->tableFilters['monthly_data_proforma']['months_to_load'] = $currentMonths + 1;
                }),
            ActionGroup::make(array_merge(
                [
                    Actions\Action::make('Toggle Tabs')
                        ->label($this->showTabs ? 'Hide Shortcuts' : 'Show Shortcuts')
                        ->tooltip('Toggle Filter Shortcuts')
                        ->color($this->showTabs ? 'secondary' : 'primary')
                        ->icon($this->showTabs ? 'heroicon-m-eye-slash' : 'heroicon-s-eye')
                        ->action('toggleTabs'),
                    PrintAction::make(),
                    ExcelImportAction::make()->color("success"),
                ],
                $this->getInvisibleTableHeaderActions() ?? []
            ))
        ];
    }

    protected static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }

    protected function getTableQuery(): Builder
    {
        return ProformaInvoice::query()
            ->whereIn('id', $this->getCachedProformaInvoiceIds())
            ->with([
                'buyer', 'category', 'grade', 'orders', 'activeOrders',
                'product', 'supplier', 'user', 'assignee', 'verifier',
            ]);
    }

    private function registerTableRenderHook()
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::HEADER_BEFORE,
            fn(): View => view('filament.resources.proforma-invoice-resource.table-tabs', ['activeTab' => $this->activeTab]),
            scopes: ListProformaInvoices::class,
        );
    }
}
