<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\PaymentResource;
use App\Models\Department;
use App\Models\Payment;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Actions\CreateAction;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public bool $showActionsAhead = true;

    public bool $showTabs;

    public ?string $activeTab = '';
    protected $listeners = [
        'refreshPage'     => '$refresh',
        'updateActiveTab',
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
        $this->resetPage();
    }

    private function registerTableRenderHook(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::HEADER_BEFORE,
            fn(): View => view('filament.resources.payment-resource.table-tabs', ['activeTab' => $this->activeTab]),
            scopes: self::class,
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
        if (! $this->showTabs) {
            $this->registerTableRenderHook();
            return [];
        }

        $specificDepartmentIds = [2, 5, 6, 8, 9, 10, 13, 18];
        $departments = Department::getSimplifiedDepartments();
        $counts = Payment::getTabCounts($specificDepartmentIds);

        $tabs = [
            null => Tab::make('All')
                ->query(fn($query) => $query)
                ->badge($counts['total'] ?? 0)
                ->icon('heroicon-o-inbox')
                ->extraAttributes(['style' => 'padding: 8px 16px;']),

            'Rial' => Tab::make('Rial')
                ->query(fn($query) => $query->where('currency', 'Rial'))
                ->badge($counts['rial_count'] ?? 0)
                ->icon('heroicon-o-currency-rupee')
                ->extraAttributes(['style' => 'padding: 8px 16px;']),

            'USD' => Tab::make('USD')
                ->query(fn($query) => $query->where('currency', 'USD'))
                ->badge($counts['usd_count'] ?? 0)
                ->icon('heroicon-o-currency-dollar')
                ->extraAttributes(['style' => 'padding: 8px 16px;']),
        ];

        foreach ($departments as $department) {
            if (in_array($department->id, $specificDepartmentIds)) {
                $tabs[$department->name] = Tab::make($department->simplified_name)
                    ->query(fn($query) => $query->whereHas('paymentRequests', fn($q) => $q->where('department_id', $department->id)))
                    ->badge($counts["department_{$department->id}_count"] ?? 0)
                    ->icon('heroicon-o-building-office') // Corrected icon name
                    ->extraAttributes(['style' => 'padding: 8px 16px;']);
            }
        }

        $tabs['Other'] = Tab::make('Other')
            ->query(function ($query) use ($specificDepartmentIds) {
                $query->whereHas('paymentRequests', function ($q) use ($specificDepartmentIds) {
                    $q->whereNotIn('department_id', $specificDepartmentIds);
                });
            })
            ->badge($counts['other_count'] ?? 0)
            ->icon('heroicon-o-ellipsis-horizontal-circle')
            ->extraAttributes(['style' => 'padding: 8px 16px;']);

        return $tabs;
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
                ->tooltip('Scroll right')
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
            CreateAction::make()
                ->label('New')
                ->url(fn() => static::getResource()::getUrl('create'))
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
                )
            )
        ];
    }

    protected function getFooterWidgets(): array
    {
        return PaymentResource::getWidgets();
    }

    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }

    protected function getTableQuery(): Builder
    {
        $query = self::getOriginalTable();

        if ($this->activeTab !== '') {

            if (in_array($this->activeTab, ['Rial','USD'])) {
                $query->where('currency', $this->activeTab);
            }
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

    /**
     * @throws \Exception
     */
    public function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->filterByUserPaymentRequests(auth()->user())
                    ->withCount([
                        'paymentRequests as has_rejected_proforma_invoice' => fn($query) => $query->whereHas('associatedProformaInvoices', fn($q) => $q->where('status', 'rejected')),
                        'paymentRequests as has_processing_payment_request' => fn($query) => $query->where('status', 'processing'),
                    ]);
            })
//            ->headerActions($this->getInvisibleTableHeaderActions())
            ->filters([
                Admin::filterDepartments(),
                Admin::filterCostCenter(),
                Admin::filterByPRCurrency(),
                Admin::filterReason(),
                Admin::filterMadeBy(),
                AdminOrder::filterSoftDeletes(),
                AdminOrder::filterCreatedAt(),
            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->filtersFormColumns(4)
            ->recordClasses(fn(Model $record) => $record->has_rejected_proforma_invoice
                ? 'bg-cancelled'
                : ($record->has_processing_payment_request ? 'bg-processing' : isShadeSelected('payment-table'))
            )
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->groupingSettingsInDropdownOnDesktop()
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->successNotification(fn(Model $record) => Admin::send($record)),
                    RestoreAction::make(),
                    Action::make('pdf')
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-c-inbox-arrow-down')
                        ->action(function (Model $record) {
                            return response()->streamDownload(function () use ($record) {
                                echo Pdf::loadView('filament.pdfs.payment', ['record' => $record])->output();
                            }, 'BMS-' . $record->reference_number . '.pdf');
                        }),
                ])
            ], position: $this->showActionsAhead ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $selectedRecords) {
                            $selectedRecords->each->delete();
                            $selectedRecords->each(
                                fn(Model $selectedRecord) => Admin::send($selectedRecord)
                            );
                        }),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                    PrintBulkAction::make(),
                ])
            ])
            ->paginated([20, 30, 40])
            ->defaultSort('created_at', 'desc')
            ->poll('120s')
            ->groups([
                Admin::filterByBalance(),
                Admin::groupByBeneficiary(),
                Admin::groupByContractor(),
                Admin::filterByCurrency(),
                Admin::filterByPaymentRequest(),
                Admin::groupByPI(),
                Admin::filterByPayer(),
                Admin::groupBySupplier(),
                Admin::filterByTransferringDate(),
            ]);
    }


    public function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showID(),
                        Admin::showPaymentRequestDep(),
                        Admin::showPaymentRequestBeneficiary(),
                        Admin::showCurrency(),
                        Admin::showRequestedAmount(),
                        Admin::showStatus(),

                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showPaymentRequest(),
                        Admin::showPaymentRequestType(),
                        Admin::showTransferredAmount(),
                        Admin::showBalance(),
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
                Admin::showPaymentRequest(),
                Admin::showContractBuyer(),
                Admin::showPaymentRequestDep(),
                Admin::showPaymentRequestCostCenter(),
                Admin::showPaymentRequestBeneficiary(),
                Admin::showAmount(),
                Admin::showBalance(),
                Admin::showPaymentRequestType(),
                Admin::showCurrency(),
                Admin::showRequestedAmount(),
                Admin::showTotalAmount(),
                Admin::showTimeGap(),
                Admin::showPayer(),
                Admin::showTransactionID(),
                Admin::showDate(),
                Admin::showCreator(),
                TableObserver::showMissingData(-3),
                Admin::showTimeStamp()
            ])->striped();
    }
}
