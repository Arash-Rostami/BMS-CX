<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\Admin;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\ListProformaInvoices;
use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceRelationManager extends RelationManager
{
    protected static string $relationship = 'proformaInvoices';

    public function clearTableSort()
    {
        $this->dispatch('clearTableSort');
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord): Table
    {
        return $table
            ->query(fn() => (
            is_null($ownRecord->order_id)
                ? $ownRecord->associatedProformaInvoices()
                : $ownRecord->order->proformaInvoice()
            )->getQuery()->select('proforma_invoices.*')
            )
            ->recordClasses(fn(Model $record) => isShadeSelected('proforma-table'))
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
            ->headerActions(
                (isModernDesign())
                    ? [ActionGroup::make((new ListProformaInvoices())->getInvisibleTableHeaderActions())]
                    : (new ListProformaInvoices())->getInvisibleTableHeaderActions()
            );
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return ProformaInvoiceResource::infolist($infolist);
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
        $ownRecord = $this->ownerRecord;

        $table = self::configureCommonTableSettings($table, $ownRecord);

        return (getTableDesign() != 'classic')
            ? ProformaInvoiceResource::getModernLayout($table)
            : ProformaInvoiceResource::getClassicLayout($table);
    }

    public function toggleFullScreen()
    {
        $this->dispatch('toggleFullScreen');
    }
}
