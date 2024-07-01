<?php

namespace App\Filament\Resources\Operational\OrderResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\PaymentRequestResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

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
        $ownRecord = $this->ownerRecord;

        $table = self::configureCommonTableSettings($table, $ownRecord);

        return (getTableDesign() != 'classic')
            ? PaymentResource::getModernLayout($table)
            : PaymentResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord): Table
    {
        return $table
            ->query(function () use ($ownRecord) {
                $invoice_number = !is_null($ownRecord->order_invoice_number) ? $ownRecord->order_invoice_number : $ownRecord->invoice_number;
                $order_id = !is_null($ownRecord->order_invoice_number) ? $ownRecord->order->id : $ownRecord->id;

                return
                    Payment::query()
                        ->whereHas('paymentRequests', function (Builder $query) use ($invoice_number, $order_id) {
                            $query->whereNull('part')
                                ->where('order_invoice_number', $invoice_number)
                                ->orWhere('part', $order_id);
                        });


            })
            ->filters([
                AdminOrder::filterCreatedAt(),
                AdminOrder::filterSoftDeletes()
            ])
            ->actions([
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn() => PaymentResource::getUrl('create'), shouldOpenInNewTab: true),
            ])
            ->poll(30);
    }
}
