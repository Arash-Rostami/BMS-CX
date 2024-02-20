<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\RelationManagers;

use App\Filament\Resources\PaymentResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use  App\Filament\Resources\Operational\OrderResource\RelationManagers\PaymentsRelationManager as OrderPayment;


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
        $table = static::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? PaymentResource::getModernLayout($table)
            : PaymentResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return OrderPayment::configureCommonTableSettings($table);
    }
}
