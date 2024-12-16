<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\PaymentRequestResource;
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


    public $showExtendedColumns = false;
    public $showActionsAhead = false;

    public $statusFilter;

    protected $listeners = ['setStatusFilter'];

    public function mount(): void
    {
        $this->showActionsAhead = $this->showActionsAhead ?? false;
    }

    protected function getTableWrapper(): string
    {
        return '<div class="scrollable-wrapper" style="overflow-x: auto; white-space: nowrap;">%s</div>';
    }


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->url(fn() => static::getResource()::getUrl('create'))
                ->icon('heroicon-o-sparkles'),
            ExcelImportAction::make()
                ->color("success"),
            PrintAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return PaymentRequestResource::getWidgets();
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


    public function getTabs(): array
    {
        return [
            null => Tab::make('All')->query(fn($query) => $query),
            'New' => Tab::make()->query(fn($query) => $query->where('status', 'pending')),
            'Processing' => Tab::make()->query(fn($query) => $query->where('status', 'processing')),
            'Allowed' => Tab::make()->query(fn($query) => $query->where('status', 'allowed')),
            'Approved' => Tab::make()->query(fn($query) => $query->where('status', 'approved')),
            'Rejected' => Tab::make()->query(fn($query) => $query->where('status', 'rejected')),
            'Fulfilled' => Tab::make()->query(fn($query) => $query->where('status', 'completed')),
            'Cancelled' => Tab::make()->query(fn($query) => $query->where('status', 'cancelled')),
            '💴﷼' => Tab::make()->query(fn($query) => $query->where('currency', 'Rial')),
            '💵$' => Tab::make()->query(fn($query) => $query->where('currency', 'USD')),
        ];
    }

    public function getTableHeaderActions(): array
    {
        $cxDep = (auth()->user()->info['department'] == 6) || getTableDesign() == 'modern';
        $design = getTableDesign() == 'modern';

        return [
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
                ->tooltip('Scroll right')
                ->color('primary')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->iconPosition(IconPosition::After)
                ->action('scrollRight'),

            Action::make('Full Screen')
                ->label('Go')
                ->tooltip('Go fullscreen')
                ->color('primary')
                ->icon('heroicon-s-arrows-pointing-out')
                ->action('toggleFullScreen'),
        ];
    }


    protected function getTableQuery(): Builder
    {
        $query = self::getOriginalTable();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
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
            ->defaultGroup('department_id')
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->filters([
                AdminOrder::filterCreatedAt(),
                Admin::filterByCurrency(),
                Admin::filterByDepartment(),
                Admin::filterByTypeOfPayment(),
                AdminOrder::filterSoftDeletes(),
            ])
            ->headerActions($this->getTableHeaderActions())
            ->filtersFormColumns(5)
            ->filtersFormWidth(MaxWidth::FourExtraLarge)
            ->recordClasses(fn(Model $record) => !isset($record->order_id) ? ($record->department_id != 6 ? 'major-row' : 'bg-light-blue') : '')
            ->searchDebounce('1000ms')
            ->groupingSettingsInDropdownOnDesktop()
            ->paginated([10, 15, 20])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->visible(fn(Model $record) => ($record->order_id !== null) || ($record->proforma_invoice_number === null))
                    ->color('info')
                    ->modalWidth(MaxWidth::Medium)
                    ->modalIcon('heroicon-o-clipboard-document-list')
                    ->record(fn(Model $record) => $record)
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
                ActionGroup::make([
                    Admin::allowRecord(),
                    Admin::approveRecord(),
                    Admin::processRecord(),
                    Admin::rejectRecord()
                ])
                    ->color('secondary')
                    ->tooltip('⇄ Change Status')
                    ->visible(fn($record) => $record->status === 'pending')
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
                Split::make([
                    Panel::make([
                        Stack::make([
                            Split::make([
                                Admin::showID(),
                                Admin::showDepartment(),
                                Admin::showReferenceNumber(),
                                Admin::showInvoiceNumber(),
                                Admin::showPart(),
                                Admin::showReasonForPayment(),
                                Admin::showType(),
                            ]),
                            Split::make([
                                Admin::showBeneficiaryName(),
                                Admin::showBankName(),
                                Admin::showAccountNumber(),
                                Admin::showStatus(),
                            ]),
                            Split::make([
                                Admin::showPayableAmount(),
                                Admin::showDeadline(),
                                TableObserver::showMissingData(-6)
                            ]),
                        ])->space(2),
                    ])
                ])->columnSpanFull(),
                Admin::showTimeStamp()
            ]);
    }


    public function getClassicLayout(Table $table)
    {
        $cxDep = (auth()->user()->info['department'] ?? 6) == 6;

        $columns = [
            Admin::showID(),
            Admin::showDepartment(),
            Admin::showCostCenter(),
            Admin::showStatus(),
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
