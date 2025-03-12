<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MainPaymentsRelationManager extends RelationManager
{
    use InteractsWithActions;

    protected static string $relationship = 'paymentRequests';

    protected static ?string $title = 'Payments (PI ⭐)';


    public bool $showActionsAhead = true;


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
        return PaymentResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $ownRecord = ($this->ownerRecord);
        $btnPosition = $this->showActionsAhead;

        $table = self::configureCommonTableSettings($table, $ownRecord, $btnPosition);

        return (getTableDesign() != 'classic')
            ? PaymentResource::getModernLayout($table)
            : PaymentResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord, $btnPosition): Table
    {

        return $table
            ->query(function () use ($ownRecord) {
                $proformaInvoiceId = $ownRecord->id;
                return Payment::from(DB::raw("(
                        SELECT DISTINCT p.*
                        FROM proforma_invoices pi
                        JOIN payment_request_proforma_invoice pri ON pi.id = pri.proforma_invoice_id
                        JOIN payment_requests pr ON pri.payment_request_id = pr.id AND pr.deleted_at IS NULL
                        JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
                        JOIN payments p ON ppr.payment_id = p.id AND p.deleted_at IS NULL
                        WHERE pi.id = {$proformaInvoiceId} AND pi.deleted_at IS NULL
                    ) AS payments"));
            })
            ->recordClasses(fn(Model $record) => isShadeSelected('payment-table'))
            ->filters([
                AdminOrder::filterCreatedAt(),
                AdminOrder::filterSoftDeletes()
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(function (?Model $record) {
                            if (!$record || !$record->id) {
                                return null;
                            }
                            return PaymentResource::getUrl('edit', ['record' => $record->id]);
                        }, shouldOpenInNewTab: true),
                ])
            ], position: $btnPosition ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->headerActions(
                array_merge([
                    Tables\Actions\Action::make('smartPayment')
                        ->label('Smart Payment')
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->modalHeading('Create Smart Payment')
                        ->modalWidth('lg')
                        ->modalSubmitActionLabel('Proceed')
                        ->action(function (array $data) {
                            $url = route('filament.admin.resources.payments.create', [
                                'id' => $data['id'], 'module' => 'proforma-invoice',
                            ]);
                            redirect()->to($url);
                        })
                        ->form([
                            Select::make('id')
                                ->label('Attach to Payment Request (PI ⭐)')
                                ->multiple()
                                ->options(fn($livewire) => $livewire->getOwnerRecord()->activeApprovedPaymentRequests
                                    ->whereIn('status', ['processing', 'approved', 'allowed'])
                                    ->whereNull('deleted_at')
                                    ->mapWithKeys(fn($pr) => [
                                        $pr->id => "{$pr->getCustomizedDisplayName()}"
                                    ])->toArray())
                                ->required(),
                        ]),
                ],
                    (isModernDesign())
                        ? [ActionGroup::make((new ListPayments())->getInvisibleTableHeaderActions())]
                        : (new ListPayments())->getInvisibleTableHeaderActions())
            )
            ->poll('30s');
    }
}
