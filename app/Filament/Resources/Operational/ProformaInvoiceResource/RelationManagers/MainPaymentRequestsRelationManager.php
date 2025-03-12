<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\ListPaymentRequests;
use App\Filament\Resources\PaymentRequestResource;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class MainPaymentRequestsRelationManager extends RelationManager
{
    use InteractsWithActions;

    protected static string $relationship = 'associatedPaymentRequests';

    protected static ?string $title = 'Payment Requests ( PI ⭐)';

    public bool $showExtendedColumns = true;

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
        return $form
            ->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return PaymentRequestResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $btnPosition = $this->showActionsAhead;
        $table = self::configureCommonTableSettings($table, $btnPosition);

        return (getTableDesign() != 'classic')
            ? PaymentRequestResource::getModernLayout($table)
            : PaymentRequestResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $btnPosition): Table
    {
        return $table
            ->recordClasses(fn(Model $record) => isShadeSelected('payment-request-table'))
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(function (?Model $record) {
                            if (!$record || !$record->id) {
                                return null;
                            }
                            return PaymentRequestResource::getUrl('edit', ['record' => $record->id]);
                        }, shouldOpenInNewTab: true),
                    Tables\Actions\Action::make('smartPayment')
                        ->label('Smart Payment')
                        ->hidden(fn(?Model $record) => $record->status == 'completed')
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->openUrlInNewTab()
                        ->url(fn($livewire, ?Model $record) => route('filament.admin.resources.payments.create', ['id' => [$record->id], 'module' => 'payment-request'])),
                ])
            ], position: $btnPosition ? ActionsPosition::BeforeCells : ActionsPosition::AfterCells)
            ->headerActions(
                array_merge([
                    Tables\Actions\Action::make('createPaymentRequest')
                        ->label('Smart Payment')
                        ->url(fn($record, $livewire) => route('filament.admin.resources.payment-requests.create', ['id' => $livewire->ownerRecord->id, 'module' => 'proforma-invoice']))
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->hidden(fn($livewire) => $livewire->ownerRecord ? $livewire->ownerRecord->activeApprovedPaymentRequests->isNotEmpty() : false)
                        ->openUrlInNewTab(),
                ],
                    (isModernDesign())
                        ? [ActionGroup::make((new ListPaymentRequests())->getInvisibleTableHeaderActions())]
                        : (new ListPaymentRequests())->getInvisibleTableHeaderActions()
                ))
            ->
            poll('30s');
    }
}
