<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\deprecated\OrderRequestResource;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\ProformaInvoice;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

//use Filament\Tables\Actions\ExportBulkAction;


class ListProformaInvoices extends ListRecords
{
    protected static string $resource = ProformaInvoiceResource::class;

    public bool $showActionsAhead = true;

    public bool $showTabs;
    public ?string $activeTab = '';


    protected $listeners = [
        'refreshPage' => '$refresh',
        'updateActiveTab'
    ];


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

    private function registerTableRenderHook()
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::HEADER_BEFORE,
            fn(): View => view('filament.resources.proforma-invoice-resource.table-tabs', ['activeTab' => $this->activeTab]),
            scopes: ListProformaInvoices::class,
        );
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

        $counts = ProformaInvoice::getTabCounts();

        $categoryTabs = [
            null => ['label' => 'All', 'icon' => 'heroicon-o-inbox'],
            'Mineral' => ['category_id' => 1, 'icon' => 'heroicon-o-cube'],
            'Polymers' => ['category_id' => 2, 'icon' => 'heroicon-m-circle-stack'],
            'Chemicals' => ['category_id' => 3, 'icon' => 'heroicon-o-beaker'],
            'Petro' => ['category_id' => 4, 'icon' => 'heroicon-s-fire'],
        ];

        $buyerTabs = [
            'Persore' => ['buyer_id' => 5],
            'Paidon' => ['buyer_id' => 2],
            'Zhuo' => ['buyer_id' => 3],
            'Solsun' => ['buyer_id' => 4],
        ];

        $statusTabs = [
            'Approved' => ['status' => 'approved', 'icon' => 'heroicon-o-check-circle'],
            'Rejected' => ['status' => 'rejected', 'icon' => 'heroicon-o-x-circle'],
            'Completed' => ['status' => 'fulfilled', 'icon' => 'heroicon-s-check-circle'],
        ];

        $tabs = [];

        foreach ($categoryTabs as $key => $config) {
            $tabs[$key] = Tab::make($config['label'] ?? $key)
                ->query(fn($query) => isset($config['category_id']) ? $query->where('category_id', $config['category_id']) : $query)
                ->badge($counts[strtolower($key) . '_count'] ?? $counts['total'] ?? 0)
                ->icon($config['icon']);
        }

        foreach ($buyerTabs as $key => $config) {
            $tabs[$key] = Tab::make($key)
                ->query(fn($query) => $query->where('buyer_id', $config['buyer_id']))
                ->badge($counts[strtolower($key) . '_count'] ?? 0)
                ->icon('heroicon-o-user-group');
        }

        foreach ($statusTabs as $key => $config) {
            $tabs[$key] = Tab::make($key === 'Rejected' ? 'Rejected/Cancelled' : $key)
                ->query(fn($query) => $query->where('status', $config['status']))
                ->badge($counts[strtolower($config['status']) . '_count'] ?? 0)
                ->icon($config['icon']);
        }

        return $tabs;
    }

    public function getInvisibleTableHeaderActions(): array
    {
        $design = isModernDesign();

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

    protected function getTableQuery(): ?Builder
    {
        $query = ProformaInvoice::query();

        $categoryTabs = [
            'Mineral' => 1,
            'Polymers' => 2,
            'Chemicals' => 3,
            'Petro' => 4,
        ];

        if (array_key_exists($this->activeTab, $categoryTabs)) {
            $query->where('category_id', $categoryTabs[$this->activeTab]);
        }

        return $query;
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
            ->filters([
                Admin::filterCategory(),
                Admin::filterProduct(),
                Admin::filterGrade(),
                Admin::filterBuyer(),
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
//                        ->record(fn(Model $record) => $record)
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
                    ExportBulkAction::make(),
//                    ExportBulkAction::make()
//                    ->chunkSize(250),
                    PrintBulkAction::make(),
                ])
            ])
            ->poll('120s')
            ->paginated([20, 30, 40])
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
            ]);
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
                Admin::showTimeStamp(),
            ])->striped();
    }
}
