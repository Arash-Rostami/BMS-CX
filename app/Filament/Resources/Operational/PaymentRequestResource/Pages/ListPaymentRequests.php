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
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\HeaderActionsPosition;
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
use Livewire\Component;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class ListPaymentRequests extends ListRecords
{

    use InteractsWithActions;

    protected static string $resource = PaymentRequestResource::class;

    public bool $showTabs;
    public $statusFilter;


    protected $listeners = ['setStatusFilter', 'refreshPage' => '$refresh'];
    public bool $showExtendedColumns = false;

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
            return [];
        }

        $counts = PaymentRequest::getTabCounts();

        return [
            null => Tab::make('All')
                ->query(fn($query) => $query)
                ->badge($counts['total'] ?? 0)
                ->icon('heroicon-o-inbox'),

            'New' => Tab::make('New')
                ->query(fn($query) => $query->where('status', 'pending'))
                ->badge($counts['pending_count'] ?? 0)
                ->icon('heroicon-o-document-plus'),

            'Processing' => Tab::make('Processing')
                ->query(fn($query) => $query->where('status', 'processing'))
                ->badge($counts['processing_count'] ?? 0)
                ->icon('heroicon-o-clock'),

            'Allowed' => Tab::make('Allowed')
                ->query(fn($query) => $query->where('status', 'allowed'))
                ->badge($counts['allowed_count'] ?? 0)
                ->icon('heroicon-o-check-circle'),

            'Approved' => Tab::make('Approved')
                ->query(fn($query) => $query->where('status', 'approved'))
                ->badge($counts['approved_count'] ?? 0)
                ->icon('heroicon-o-check-badge'),

            'Rejected' => Tab::make('Rejected')
                ->query(fn($query) => $query->where('status', 'rejected'))
                ->badge($counts['rejected_count'] ?? 0)
                ->icon('heroicon-o-x-circle'),

            'Completed' => Tab::make('Completed')
                ->query(fn($query) => $query->where('status', 'completed'))
                ->badge($counts['completed_count'] ?? 0)
                ->icon('heroicon-s-check-circle'),

            'Rial' => Tab::make('Rial')
                ->query(fn($query) => $query->where('currency', 'Rial'))
                ->badge($counts['rial_count'] ?? 0)
                ->icon('heroicon-o-currency-rupee'),

            'USD' => Tab::make('USD')
                ->query(fn($query) => $query->where('currency', 'USD'))
                ->badge($counts['usd_count'] ?? 0)
                ->icon('heroicon-o-currency-dollar'),

            'Advance' => Tab::make('Advance')
                ->query(fn($query) => $query->where('type_of_payment', 'advance'))
                ->badge($counts['advance_count'] ?? 0)
                ->icon('heroicon-s-arrow-left-circle'),

            'Balance' => Tab::make('Balance')
                ->query(fn($query) => $query->where('type_of_payment', 'balance'))
                ->badge($counts['balance_count'] ?? 0)
                ->icon('heroicon-s-arrow-right-circle'),

            'Other' => Tab::make('Other')
                ->query(fn($query) => $query->where('type_of_payment', 'other'))
                ->badge($counts['other_count'] ?? 0)
                ->icon('heroicon-o-ellipsis-horizontal-circle'),
        ];
    }


    public function getTableHeaderActions(): array
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

        if ($design) {
            return [ActionGroup::make($actions)];
        }

        return $actions;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->url(fn() => static::getResource()::getUrl('create'))
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


    protected function getTableQuery(): Builder
    {
        //        if ($this->statusFilter) {
//            $query->where('status', $this->statusFilter);
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

            ], layout: FiltersLayout::AboveContentCollapsible)
            ->headerActions($this->getTableHeaderActions())
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
                    Admin::processRecord(),
                    Admin::rejectRecord()
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
