<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\deprecated\OrderRequestResource;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\ProformaInvoice;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ListProformaInvoices extends ListRecords
{
    protected static string $resource = ProformaInvoiceResource::class;

    public bool $showActionsAhead = true;

    public bool $showTabs;


    protected $listeners = ['refreshPage' => '$refresh'];


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

    public function clearTableSort()
    {
        $this->dispatch('clearTableSort');
    }

    public function getTabs(): array
    {
        if (!$this->showTabs) {
            return [];
        }

        $counts = ProformaInvoice::getTabCounts();

        return [
            null => Tab::make('All')
                ->query(fn($query) => $query)
                ->badge($counts['total'] ?? 0)
                ->icon('heroicon-o-inbox'),

            'Mineral' => Tab::make('Mineral')
                ->query(fn($query) => $query->where('category_id', 1))
                ->badge($counts['mineral_count'] ?? 0)
                ->icon('heroicon-o-cube'),

            'Polymers' => Tab::make('Polymers')
                ->query(fn($query) => $query->where('category_id', 2))
                ->badge($counts['polymers_count'] ?? 0)
                ->icon('heroicon-m-circle-stack'),

            'Chemicals' => Tab::make('Chemicals')
                ->query(fn($query) => $query->where('category_id', 3))
                ->badge($counts['chemicals_count'] ?? 0)
                ->icon('heroicon-o-beaker'),

            'Petro' => Tab::make('Petroleum')
                ->query(fn($query) => $query->where('category_id', 4))
                ->badge($counts['petro_count'] ?? 0)
                ->icon('heroicon-s-fire'),

            'Persore' => Tab::make('Persore')
                ->query(fn($query) => $query->where('buyer_id', 5))
                ->badge($counts['persore_count'] ?? 0)
                ->icon('heroicon-o-user-group'),

            'Paidon' => Tab::make('Paidon')
                ->query(fn($query) => $query->where('buyer_id', 2))
                ->badge($counts['paidon_count'] ?? 0)
                ->icon('heroicon-o-user-group'),

            'Zhuo' => Tab::make('Zhuo')
                ->query(fn($query) => $query->where('buyer_id', 3))
                ->badge($counts['zhuo_count'] ?? 0)
                ->icon('heroicon-o-user-group'),

            'Solsun' => Tab::make('Solsun')
                ->query(fn($query) => $query->where('buyer_id', 4))
                ->badge($counts['solsun_count'] ?? 0)
                ->icon('heroicon-o-user-group'),

            'Approved' => Tab::make('Approved')
                ->query(fn($query) => $query->where('status', 'approved'))
                ->badge($counts['approved_count'] ?? 0)
                ->icon('heroicon-o-check-circle'),

            'Rejected' => Tab::make('Rejected/Cancelled')
                ->query(fn($query) => $query->where('status', 'rejected'))
                ->badge($counts['rejected_count'] ?? 0)
                ->icon('heroicon-o-x-circle'),

            'Completed' => Tab::make('Completed')
                ->query(fn($query) => $query->where('status', 'fulfilled'))
                ->badge($counts['fulfilled_count'] ?? 0)
                ->icon('heroicon-s-check-circle'),
        ];
    }

    public function getTableHeaderActions(): array
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

    protected static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }

    protected function getTableQuery(): ?Builder
    {
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
            ->filters([
                Admin::filterCategory(),
                Admin::filterProduct(),
                Admin::filterGrade(),
                Admin::filterBuyer(),
                Admin::filterSupplier(),
                Admin::filterProforma(),
                Admin::filterPart(),
                Admin::filterStatus(),
                Admin::filterCreator(),
                AdminOrder::filterSoftDeletes(),
                Admin::filterTelexNeeded(),
            ], layout: FiltersLayout::AboveContentCollapsible)
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
                                echo Pdf::loadHtml(view('filament.pdfs.proformaInvoice', ['record' => $record])
                                    ->render())
                                    ->stream();
                            }, 'BMS-' . $record->reference_number . '.pdf');
                        }),
                    Action::make('createPaymentRequest')
                        ->label('Smart Payment')
                        ->url(fn($record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'proforma-invoice']))
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->openUrlInNewTab(),
                    ReplicateAction::make()
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
                        Admin::showContractName(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showBuyer(),
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
                Admin::showCategory(),
                Admin::showProduct(),
                Admin::showGrade(),
                Admin::showPrice(),
                Admin::showQuantity(),
                Admin::showPercentage(),
                Admin::showTotal(),
                Admin::showShipmentPart(),
                Admin::showContractName(),
                Admin::showCreator(),
                Admin::showAssignedTo(),
                Admin::showTimeStamp(),
            ])->striped();
    }
}
