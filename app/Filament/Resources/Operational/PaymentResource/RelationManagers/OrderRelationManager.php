<?php

namespace App\Filament\Resources\Operational\PaymentResource\RelationManagers;

use App\Filament\Resources\Operational\PaymentRequestResource\RelationManagers\OrderRelationManager as PaymentRequestOrder;
use App\Filament\Resources\OrderResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderRelationManager extends RelationManager
{
    protected static string $relationship = 'order';


    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return OrderResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? OrderResource::getModernLayout($table)
            : OrderResource::getClassicLayout($table);
    }


    public static function configureCommonTableSettings(Table $table): Table
    {
        return PaymentRequestOrder::configureCommonTableSettings($table);
    }
}
