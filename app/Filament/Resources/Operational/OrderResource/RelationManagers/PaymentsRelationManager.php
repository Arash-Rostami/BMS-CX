<?php

namespace App\Filament\Resources\Operational\OrderResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentRequestResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
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

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

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
        $table = self::configureCommonTableSettings($table, $ownRecord);

        return (getTableDesign() != 'classic')
            ? PaymentResource::getModernLayout($table)
            : PaymentResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord): Table
    {
        return $table
            ->query(function () use ($ownRecord) {
                return Payment::query()
                    ->whereNull('deleted_at')
                    ->whereHas('paymentRequests', function (Builder $query) use ($ownRecord) {
                        $query->whereNull('deleted_at')->where('order_id', $ownRecord->id);
                    });
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
                        }, shouldOpenInNewTab: true)
                ])
            ], position: ActionsPosition::BeforeCells)
            ->headerActions(
                array_merge(
                    [
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
                                    ->label('Attach to Payment Request')
                                    ->multiple()
                                    ->options(fn($livewire) => $livewire->getOwnerRecord()->paymentRequests
                                        ->whereIn('status', ['processing', 'approved', 'allowed'])
                                        ->whereNull('deleted_at')
                                        ->mapWithKeys(fn($pr) => [
                                            $pr->id => "{$pr->getCustomizedDisplayName()}"
                                        ])->toArray())
                                    ->required(),
                            ])
                    ],
                    (isModernDesign())
                        ? [ActionGroup::make( (new ListPayments())->getInvisibleTableHeaderActions())]
                        :  (new ListPayments())->getInvisibleTableHeaderActions()
                ))
            ->poll('30s');
    }
}
