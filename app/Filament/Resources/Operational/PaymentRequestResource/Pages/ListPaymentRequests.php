<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use niklasravnsborg\LaravelPdf\Facades\Pdf;



class ListPaymentRequests extends ListRecords
{

    use InteractsWithActions;

    protected static string $resource = PaymentRequestResource::class;

    public bool $showTabs;
    public $statusFilter;


    public bool $showExtendedColumns = false;

    public bool $showActionsAhead = true;

    public ?string $activeTab = '';
    protected $listeners = [
        'setStatusFilter',
        'refreshPage' => '$refresh',
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
            fn(): View => view('filament.resources.payment-request-resource.table-tabs', ['activeTab' => $this->activeTab]),
            scopes: self::class,
        );
    }


    public function toggleTabs()
    {
        $this->showTabs = !$this->showTabs;
        $this->dispatch('refreshPage');
    }


    public function setStatusFilter($filter)
    {
        $this->statusFilter = $filter === 'total' ? null : $filter;
        $this->resetPage();
    }

    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }

    public function toggleExtendedColumns()
    {
        $this->showExtendedColumns = !$this->showExtendedColumns;
        $this->resetPage();

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

        $counts = PaymentRequest::getTabCounts();

        $tabConfigs = [
            // Status tabs
            ['column' => 'status', 'value' => 'pending', 'label' => 'New', 'icon' => 'heroicon-o-document-plus', 'count_key' => 'pending_count'],
            ['column' => 'status', 'value' => 'processing', 'label' => 'Processing', 'icon' => 'heroicon-o-clock', 'count_key' => 'processing_count'],
            ['column' => 'status', 'value' => 'allowed', 'label' => 'Allowed', 'icon' => 'heroicon-o-check-circle', 'count_key' => 'allowed_count'],
            ['column' => 'status', 'value' => 'approved', 'label' => 'Approved', 'icon' => 'heroicon-o-check-badge', 'count_key' => 'approved_count'],
            ['column' => 'status', 'value' => 'rejected', 'label' => 'Rejected', 'icon' => 'heroicon-o-x-circle', 'count_key' => 'rejected_count'],
            ['column' => 'status', 'value' => 'completed', 'label' => 'Completed', 'icon' => 'heroicon-s-check-circle', 'count_key' => 'completed_count'],
            // Currency tabs
            ['column' => 'currency', 'value' => 'Rial', 'label' => 'Rial', 'icon' => 'heroicon-o-currency-rupee', 'count_key' => 'rial_count'],
            ['column' => 'currency', 'value' => 'USD', 'label' => 'USD', 'icon' => 'heroicon-o-currency-dollar', 'count_key' => 'usd_count'],
            // Type tabs
            ['column' => 'type_of_payment', 'value' => 'advance', 'label' => 'Advance', 'icon' => 'heroicon-o-credit-card', 'count_key' => 'advance_count'],
            ['column' => 'type_of_payment', 'value' => 'balance', 'label' => 'Balance', 'icon' => 'heroicon-o-scale', 'count_key' => 'balance_count'],
            ['column' => 'type_of_payment', 'value' => 'other', 'label' => 'Other', 'icon' => 'heroicon-o-ellipsis-horizontal-circle', 'count_key' => 'other_count'],
        ];

        $tabs = [
            null => Tab::make('All', null)
                ->query(fn($query) => $query)
                ->badge($counts['total'] ?? 0)
                ->icon('heroicon-o-inbox'),
        ];

        foreach ($tabConfigs as $config) {
            $tabs[$config['value']] = Tab::make($config['label'], $config['value'])
                ->query(function ($query) use ($config) {
                    return $query->where($config['column'], $config['value']);
                })
                ->badge($counts[$config['count_key']] ?? 0)
                ->icon($config['icon']);
        }

        return $tabs;
    }


    public function getInvisibleTableHeaderActions(): array
    {
        $cxDep = (auth()->user()->info['department'] == 6) || isModernDesign();
        $design = getTableDesign() == 'modern';

        $actions = [
            Action::make('Refresh Sorting')
                ->label('Reset')
                ->tooltip('Reset Column Orders')
                ->color('primary')
                ->icon('heroicon-m-receipt-refund')
                ->action('clearTableSort'),

            Action::make('Toggle Extended Info')
                ->action('toggleExtendedColumns')
                ->color(fn() => $this->showExtendedColumns ? 'secondary' : 'primary')
                ->icon(fn() => $this->showExtendedColumns ? 'heroicon-c-eye-slash' : 'heroicon-o-eye')
                ->label('Details')
                ->tooltip(fn() => $this->showExtendedColumns ? 'Hide Extended Details' : 'Show Extended Details')
                ->visible(!$cxDep),

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


    protected function getTableQuery(): Builder
    {
        $query = self::getOriginalTable();

        if ($this->activeTab) {
            $statusTabs = ['pending', 'processing', 'allowed', 'approved', 'rejected', 'completed'];
            $currencyTabs = ['Rial', 'USD'];

            $column = 'type_of_payment';

            if (in_array($this->activeTab, $statusTabs)) {
                $column = 'status';
            } elseif (in_array($this->activeTab, $currencyTabs)) {
                $column = 'currency';
            }

            $query->where($column, $this->activeTab);
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
            ->modifyQueryUsing(fn(Builder $query) => $query->authorizedForUser(auth()->user()))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->filters([
                Admin::filterByDepartment(),
                Admin::filterByCostCenter(),
                Admin::filterByTypeOfPayment(),
                Admin::filterByReason(),
                Admin::filterByCurrency(),
                Admin::filterBySupplier(),
                Admin::filterByContractor(),
                Admin::filterByBeneficiary(),
                Admin::filterByUpcomingDeadline(),
                Admin::filterByStatus(),
                AdminOrder::filterCreatedAt(),
                Admin::filterByCaseNumber(),
                Admin::filterByPaymentMethod(),
                Admin::filterByBankName(),
                AdminOrder::filterSoftDeletes(),

            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->filtersFormColumns(6)
//            ->headerActions($this->getTableHeaderActions())
            ->filtersFormColumns(5)
            ->filtersFormWidth(MaxWidth::FourExtraLarge)
            ->recordClasses(fn(Model $record) => Admin::changeBgColor($record))
            ->searchDebounce('1000ms')
            ->groupingSettingsInDropdownOnDesktop()
            ->paginated([20, 30, 40])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->authorize('create')
                        ->visible(fn(Model $record) => ($record->order_id !== null) || ($record->proforma_invoice_number === null))
                        ->color('info')
                        ->modalWidth(MaxWidth::Medium)
                        ->modalIcon('heroicon-o-clipboard-document-list')
//                    ->record(fn(Model $record) => $record)
                        ->requiresConfirmation()
                        ->modalHeading('Notice')
                        ->modalDescription(fn(Model $record) => optional($record->department)->code === 'CX' ?
                            'Replicating this payment request will create a new record for the same order. To attach it to a different order, use either Smart Payment from \'Order Module\' or create a new payment request.' :
                            'Are you sure you want to replicate payment request ' . $record->reference_number . '?'
                        )
                        ->modalSubmitActionLabel('Replicate')
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['user_id'] = auth()->id();
                            return $data;
                        })
                        ->beforeReplicaSaved(fn(Model $replica) => $replica->status = 'pending')
                        ->after(fn(Model $replica) => Admin::syncPaymentRequest($replica))
                        ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.payment-requests.edit', ['record' => $replica->id,])),
                    DeleteAction::make()
                        ->hidden(fn(?Model $record) => $record ? $record->payments->isNotEmpty() : false)
                        ->successNotification(fn(Model $record) => Admin::send($record)),
                    RestoreAction::make(),
                    Admin::allowRecord(),
                    Admin::approveRecord(),
                    Admin::processRecord()
                        ->authorize('edit'),
                    Admin::rejectRecord()
                        ->authorize('edit'),
                    Action::make('pdf')
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-c-inbox-arrow-down')
                        ->action(function (Model $record) {
                            return response()->streamDownload(function () use ($record) {
                                echo Pdf::loadView('filament.pdfs.paymentRequest', ['record' => $record])->output();
                            }, 'BMS-' . $record->reference_number . '.pdf');
                        }),
                    Action::make('smartPayment')
                        ->label('Smart Payment')
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->openUrlInNewTab()
                        ->url(fn($record) => route('filament.admin.resources.payments.create', ['id' => [$record->id], 'module' => 'payment-request'])),

//                    ->color('secondary')
//                    ->tooltip('⇄ Change Status')
//                    ->visible(fn($record) => $record->status === 'pending')
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
            ->defaultSort('created_at', 'desc')
            ->poll('120s')
            ->groups([
                Admin::groupByDepartment(),
                Admin::groupByOrder(),
                Admin::groupByReason(),
                Admin::groupByType(),
                Admin::groupByCurrency(),
                Admin::groupByContractor(),
                Admin::groupBySupplier(),
                Admin::groupByBeneficiary(),
                Admin::groupByStatus(),
                Admin::groupByCase(),
            ]);
    }

    public function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showID(),
                        Admin::showDepartment(),
                        Admin::showBeneficiaryName(),
                        Admin::showDeadline(),
                        Admin::showStatus(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Stack::make([
                            Admin::showType(),
                            Admin::showReasonForPayment(),
                        ])->grow(false),
                        Stack::make([
                            Admin::showPayableAmount(),
                            Admin::showAccountNumber(),
                        ])->grow(false),
                        Admin::showPart(),
                        Admin::showCaseNumber(),
                    ])->columnSpan(3),
                ])->collapsible(),
            ]);
    }


    public function getClassicLayout(Table $table)
    {
        $cxDep = (auth()->user()->info['department'] ?? 6) == 6;

        $columns = [
            Admin::showID(),
            Admin::showStatus(),
            Admin::showDepartment(),
            Admin::showCostCenter(),
            Admin::showType(),
            Admin::showReasonForPayment(),
            Admin::showPayableAmount(),
            Admin::showBankName(),
            Admin::showBeneficiaryName(),
            Admin::showAccountNumber(),
            Admin::showDeadline(),
            Admin::showRequestMaker(),
            Admin::showStatusChanger(),
            TableObserver::showMissingData(-5),
        ];

        $extendedColumnsCx = [
            Admin::showProformaInvoiceNumber()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showInvoiceNumber()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showReferenceNumber()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showPart()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
        ];

        $extendedColumnsExtra = [
            Admin::showCaseNumber()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showBeneficiaryAddress()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showBankAddress()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showExtraDescription()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showSwiftCode()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showIBAN()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showIFSC()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
            Admin::showMICR()->hidden(fn() => !($cxDep || $this->showExtendedColumns)),
        ];

        array_splice($columns, 4, 0, $extendedColumnsCx);
        array_splice($columns, 15, 0, $extendedColumnsExtra);

        return $table->columns($columns)->striped();
    }
}
