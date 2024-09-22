<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\Admin;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\Order;
use App\Models\ProformaInvoice;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceRelationManager extends RelationManager
{
    protected static string $relationship = 'proformaInvoices';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return ProformaInvoiceResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $ownRecord = $this->ownerRecord;

        $table = self::configureCommonTableSettings($table, $ownRecord);

        return (getTableDesign() != 'classic')
            ? ProformaInvoiceResource::getModernLayout($table)
            : ProformaInvoiceResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord): Table
    {
        return $table
            ->query(function () use ($ownRecord) {
                return is_null($ownRecord->order_id)
                    ? $ownRecord->associatedProformaInvoices()
                    : $ownRecord->order->proformaInvoice();
            })
            ->filters([
                Admin::filterProforma(),
                AdminOrder::filterSoftDeletes()
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(function (?Model $record) {
                        if (!$record || !$record->id) {
                            return null;
                        }
                        return ProformaInvoiceResource::getUrl('edit', ['record' => $record->id]);
                    }, shouldOpenInNewTab: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn() => ProformaInvoiceResource::getUrl('create'), shouldOpenInNewTab: true),
            ])
            ->poll(30);
    }
}
