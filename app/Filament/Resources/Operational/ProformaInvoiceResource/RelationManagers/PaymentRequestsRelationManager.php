<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\ListPaymentRequests;
use App\Filament\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentRequestsRelationManager extends RelationManager
{
    use InteractsWithActions;

    protected static string $relationship = 'paymentRequests';
    protected static ?string $inverseRelationship = 'proformaInvoices';

    protected static ?string $title = 'Payment Requests ( Orders 🛒)';

    public bool $showExtendedColumns = false;

    public bool $showActionsAhead = true;


    public function clearTableSort()
    {
        $this->dispatch('clearTableSort');
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord, $btnPosition): Table
    {
        $id = $ownRecord->id;

        return $table
            ->query(function () use ($id) {
                return PaymentRequest::from(DB::raw("(
                    SELECT DISTINCT pr.*
                    FROM proforma_invoices pi
                    JOIN orders o
                        ON pi.id = o.proforma_invoice_id
                        AND o.deleted_at IS NULL
                    JOIN payment_requests pr
                        ON o.id = pr.order_id
                        AND pr.deleted_at IS NULL
                    WHERE pi.id = {$id}
                      AND pi.deleted_at IS NULL
                ) AS payment_requests"));
            })
            ->recordClasses(fn(Model $record) => isShadeSelected('payment-request-table'))
            ->searchable()
            ->recordAction(null)
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultGroup('reason_for_payment')
            ->filters([
                AdminOrder::filterCreatedAt(),
                Admin::filterByCurrency(),
                Admin::filterByTypeOfPayment(),
                AdminOrder::filterSoftDeletes(),
            ])
            ->groups([
                Admin::groupByOrder(),
                Admin::groupByReason(),
                Admin::groupByType(),
                Admin::groupByCurrency(),
                Admin::groupByContractor($id),
                Admin::groupBySupplier($id),
                Admin::groupByBeneficiary(),
                Admin::groupByStatus(),
                Admin::groupByCase(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(fn(?Model $record) => $record?->id ? PaymentRequestResource::getUrl('edit', ['record' => $record->id]) : null, true),
                    Tables\Actions\Action::make('smartPayment')
                        ->label('Smart Payment')
                        ->icon('heroicon-o-credit-card')
                        ->hidden(fn(?Model $record) => $record->status == 'completed')
                        ->color('warning')
                        ->openUrlInNewTab()
                        ->url(fn(?Model $record) => $record?->id ? route('filament.admin.resources.payments.create', ['id' => [$record->id], 'module' => 'payment-request']) : null),
                ])
            ], position: $btnPosition ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->headerActions(
                array_merge(
                    [
                        Tables\Actions\Action::make('smartPaymentRequest')
                            ->label('Smart Payment')
                            ->icon('heroicon-o-credit-card')
                            ->color('warning')
                            ->modalHeading('Create Smart Payment')
                            ->modalWidth('sm')
                            ->modalSubmitActionLabel('Proceed')
                            ->action(fn(array $data) => redirect()->to(route('filament.admin.resources.payment-requests.create', ['id' => $data['id'], 'module' => 'order', 'type' => $data['payment_type']])))
                            ->form([
                                Select::make('id')
                                    ->label('Attach to Order')
                                    ->options(fn($livewire) => $livewire->getOwnerRecord()->orders->mapWithKeys(fn($order) => [
                                        $order->id => "Part: {$order->part} ({$order->reference_number})"
                                    ])->toArray())
                                    ->required(),
                                Select::make('payment_type')
                                    ->label('Payment Type')
                                    ->options([
                                        'balance' => 'Balance',
                                        'partial' => 'Partial',
                                        'other' => 'Other',
                                    ])
                                    ->tooltip(' To auto-compute the remaining balance, please ensure the order amount has been calculated in the order record before selecting \'Balance\'.')
                                    ->required(),

                            ]),
                    ],
                    (isModernDesign())
                        ? [ActionGroup::make((new ListPaymentRequests())->getInvisibleTableHeaderActions())]
                        : (new ListPaymentRequests())->getInvisibleTableHeaderActions()
                ))
            ->searchDebounce('1000ms')
            ->poll('300s');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return PaymentRequestResource::infolist($infolist);
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
        $table = self::configureCommonTableSettings($table, $this->ownerRecord, $this->showActionsAhead);

        $listPaymentRequests = new ListPaymentRequests();
        $listPaymentRequests->showExtendedColumns = (bool)$this->showExtendedColumns;

        return (getTableDesign() != 'classic')
            ? $listPaymentRequests->getModernLayout($table)
            : $listPaymentRequests->getClassicLayout($table);
    }

    public function toggleExtendedColumns()
    {
        $this->showExtendedColumns = !$this->showExtendedColumns;
        $this->dispatch('$refresh');
    }

    public function toggleFullScreen()
    {
        $this->dispatch('toggleFullScreen');
    }
}
