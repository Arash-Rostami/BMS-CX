<?php

namespace App\Filament\Resources\Operational\PaymentResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\RelationManagers\PaymentRequestsRelationManager as OrderPaymentRequests;
use App\Filament\Resources\PaymentRequestResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentRequests';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return PaymentRequestResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? PaymentRequestResource::getModernLayout($table)
            : PaymentRequestResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return OrderPaymentRequests::configureCommonTableSettings($table);
    }
}
