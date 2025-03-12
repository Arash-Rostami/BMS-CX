<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\PortOfDelivery;
use App\Models\ProformaInvoice;
use App\Services\InfoExtractor;
use App\Services\ProjectNumberGenerator;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use PHPUnit\Metadata\Group;
use function PHPUnit\Framework\isEmpty;


class OrdersRelationManager extends RelationManager
{
    use InteractsWithActions;

    protected static string $relationship = 'orders';

    protected $listeners = ['refreshTable' => '$refresh'];


    public bool $showActionsAhead = true;


    public function toggleExtendedColumns()
    {
        return true;
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

    public function moveActionsToStart()
    {
        $this->showActionsAhead = !$this->showActionsAhead;
        $this->dispatch('$refresh');
    }

    public function resetActionsToEnd()
    {
        $this->showActionsAhead = !$this->showActionsAhead;
        $this->dispatch('$refresh');
    }


    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return OrderResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $btnPosition = $this->showActionsAhead;

        $table = self::configureCommonTableSettings($table, $btnPosition);

        return (getTableDesign() != 'classic')
            ? OrderResource::getModernLayout($table)
            : OrderResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $btnPosition): Table
    {
        return $table
            ->defaultGroup('invoice_number')
            ->defaultSort('part', 'asc')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->recordClasses(fn(Model $record) => $record->hasCompletedBalancePayment()
                ? (isModernDesign() ? 'order-modern-telex' : 'order-classic-telex')
                : isShadeSelected('order-table'))
            ->groupingSettingsInDropdownOnDesktop()
            ->filters([
                Admin::filterSoftDeletes(),
                Admin::filterBasedOnQuery()
            ],  layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->filtersFormColumns(6)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(fn(?Model $record) => (!$record || !$record->id) ? null : OrderResource::getUrl('edit', ['record' => $record->id]), shouldOpenInNewTab: true),
                    ReplicateAction::make()
                        ->color('info')
                        ->modalWidth(MaxWidth::Medium)
                        ->modalIcon('heroicon-o-clipboard-document-list')
//                        ->visible(fn($record) => Admin::isPaymentCalculated($record))
                        ->beforeReplicaSaved(function (Model $replica) {
                            Admin::increasePart($replica);
                            Admin::replicateRelatedModels($replica);
                        })
                        ->after(fn(Model $replica) => Admin::syncOrder($replica))
                        ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.orders.edit', ['record' => $replica->id,])),
                    DeleteAction::make()
                        ->successNotification(fn(Model $record) => Admin::send($record))
                        ->hidden(fn(?Model $record) => $record?->paymentRequests->isNotEmpty() ?? false),
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
                ]),
            ], position: $btnPosition ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->headerActions(
                array_merge(
                    [
                        Action::make('replicate_last')
                            ->label('Add')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->modalHeading('Create Order')
                            ->modalDescription('Are you sure you want to add order to this proforma invoice?')
                            ->action(function ($livewire) {
                                $proformaInvoice = $livewire->getOwnerRecord();
                                $lastOrder = $proformaInvoice->orders()->latest()->first();

                                if ($lastOrder) {
                                    $replica = $lastOrder->replicate();
//                                    self::updateAutoCompute($replica);
                                    $replica->proforma_invoice_id = $proformaInvoice->id;
                                    $replica->save();

                                    Admin::replicateRelatedModels($replica);
                                    Admin::increasePart($replica);
                                    Admin::updatePortData($proformaInvoice, $replica);
                                    Admin::syncOrder($replica);
                                } else {
                                    $newOrder = new Order();
                                    self::populateNewOrderFields($newOrder, $proformaInvoice);
                                    Admin::syncOrder($newOrder);
                                }
                                Notification::make()
                                    ->title('Success')
                                    ->body('Order created successfully.')
                                    ->success()
                                    ->send();
                                $livewire->dispatch('refreshTable');
                            }),
                        Action::make('replicate_specific_part')
                            ->label('New')
                            ->icon('heroicon-o-folder-plus')
                            ->color('primary')
                            ->modalSubmitActionLabel('Create')
                            ->modalWidth('sm')
                            ->modalHeading('Create Specific Part')
                            ->form([
                                Select::make('part')
                                    ->label('Part')
                                    ->options(function ($livewire) {
                                        $proformaInvoice = $livewire->getOwnerRecord();
                                        $maxPart = $proformaInvoice->part ?? 0;
                                        $existingParts = $proformaInvoice->orders()->pluck('part')->toArray();
                                        $maxPart = ($maxPart > 0) ? $maxPart : 100;
                                        $availableParts = array_diff(range(1, $maxPart), $existingParts);
                                        return array_combine($availableParts, $availableParts);
                                    })
                                    ->required()
                                    ->placeholder('Select the number'),
                            ])
                            ->action(function (array $data, $livewire) {
                                $part = $data['part'];
                                $proformaInvoice = $livewire->getOwnerRecord();

                                $newOrder = new Order();
                                self::populateNewOrderFields($newOrder, $proformaInvoice, $part);
                                Admin::syncOrder($newOrder);

                                Notification::make()
                                    ->title('Success')
                                    ->body('Order made successfully for Part ' . $part)
                                    ->success()
                                    ->send();
                                $livewire->dispatch('refreshTable');
                            }),
                    ],
                       (isModernDesign())
                           ? [ActionGroup::make( (new ListOrders())->getInvisibleTableHeaderActions())]
                           : (new ListOrders())->getInvisibleTableHeaderActions()
                )
            )
            ->groups([
                Admin::groupByCurrency(),
                Admin::groupByInvoiceNumber(),
                Admin::groupByPart(),
                Admin::groupByGrade(),
                Admin::groupByTags(),
            ])
            ->poll('200s');
    }


    private static function populateNewOrderFields(Order $newOrder, ProformaInvoice $proformaInvoice, $part = null): void
    {
        list($matchedPortData, $portOfDeliveryId) = Admin::extractPortData($proformaInvoice, $part ?? '1');

        if (!$newOrder->order_detail_id) {
            $orderDetail = $newOrder->orderDetail()->create([
                'buying_price' => $proformaInvoice->price ?? '',
                'buying_quantity' => $proformaInvoice->quantity ?? '',
                'provisional_price' => $proformaInvoice->price ?? '',
                'currency' => 'USD',
                'provisional_quantity' => $matchedPortData ? $matchedPortData['quantity'] : ($proformaInvoice->quantity ?? ''),
                'extra' => array_merge([
                    'percentage' => $proformaInvoice->percentage ?? '',
                    'manualComputation' => false,
                    'lastOrder' => false,
                    'allOrders' => false
                ], $newOrder->orderDetail->extra ?? [])
            ]);
            $newOrder->order_detail_id = $orderDetail->id;
        }

        if (!$newOrder->party_id) {
            $party = $newOrder->party()->create([
                'buyer_id' => $proformaInvoice->buyer_id ?? '',
                'supplier_id' => $proformaInvoice->supplier_id ?? '',
            ]);
            $newOrder->party_id = $party->id;
        }

        if (!$newOrder->logistic_id) {
            $logistic = $newOrder->logistic()->create([
                'port_of_delivery_id' => $portOfDeliveryId ?? null,
                'extra' => ['loading_startline' => null, 'etd' => null, 'eta' => null]
            ]);
            $newOrder->logistic_id = $logistic->id;
        }


        if (!$newOrder->doc_id) {
            $doc = $newOrder->doc()->create([
                'extra' => ['voyage_number_second_leg' => null, 'BL_number_second_leg' => null, 'BL_date_second_leg' => null]
            ]);
            $newOrder->doc_id = $doc->id;
        }

        $newOrder->proforma_number = $proformaInvoice->proforma_number ?? '';
        $newOrder->proforma_date = $proformaInvoice->proforma_date ?? '';
        $newOrder->category_id = $proformaInvoice->category_id ?? '';
        $newOrder->product_id = $proformaInvoice->product_id ?? '';
        $newOrder->grade_id = $proformaInvoice->grade_id ?? 0;
        $newOrder->purchase_status_id = '1';
        $newOrder->part = $part ?? '1';
        $newOrder->order_status = 'processing';
        $newOrder->proforma_invoice_id = $proformaInvoice->id;
        $newOrder->invoice_number = $proformaInvoice->contract_number ?? ProjectNumberGenerator::generate();

        $newOrder->save();
    }
}
