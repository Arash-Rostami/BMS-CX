<?php

namespace App\Filament\Resources\Operational\OrderResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceRelationManagers extends RelationManager
{
    protected static string $relationship = 'proformaInvoice';

    protected static ?string $title = 'Pro forma Invoice';

    public function form(Form $form): Form
    {
        return $form->schema([ ]);
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
            ->actions([
                Tables\Actions\ViewAction::make(),
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
