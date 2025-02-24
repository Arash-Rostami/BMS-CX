<?php

namespace App\Filament\Resources\Operational\OrderResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\ListProformaInvoices;
use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceRelationManagers extends RelationManager
{
    protected static string $relationship = 'proformaInvoice';

    protected static ?string $title = 'Pro forma Invoice';

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
        return ProformaInvoiceResource::infolist($infolist);
    }


    public function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? ProformaInvoiceResource::getModernLayout($table)
            : ProformaInvoiceResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {

        return $table
            ->filters([
                AdminOrder::filterProforma(),
                AdminOrder::filterSoftDeletes()
            ])
            ->recordClasses(fn(Model $record) => ($record->status == 'rejected')
                ? 'bg-cancelled'
                : ($record->hasCompletedBalancePayment()
                    ? (isModernDesign() ? 'proforma-modern-telex' : 'proforma-classic-telex')
                    : isShadeSelected('proforma-table')))
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(function (?Model $record) {
                            if (!$record || !$record->id) {
                                return null;
                            }
                            return ProformaInvoiceResource::getUrl('edit', ['record' => $record->id]);
                        }, shouldOpenInNewTab: true)
                ])
            ], position: ActionsPosition::BeforeCells)
            ->headerActions(
                array_merge(
                    [
                        Tables\Actions\CreateAction::make()
                            ->label('New')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->url(fn() => ProformaInvoiceResource::getUrl('create'), shouldOpenInNewTab: true),
                    ],
                    (new ListProformaInvoices())->getTableHeaderActions()
                )
            )
            ->poll('30s');
    }
}
