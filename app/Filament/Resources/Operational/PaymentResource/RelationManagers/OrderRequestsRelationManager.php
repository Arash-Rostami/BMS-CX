<?php

namespace App\Filament\Resources\Operational\PaymentResource\RelationManagers;

use App\Filament\Resources\OrderRequestResource;
use App\Models\OrderRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use Illuminate\Database\Eloquent\Model;


class OrderRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderRequests';

    public function form(Form $form): Form
    {
        return $form
            ->schema([ ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return OrderRequestResource::infolist($infolist);
    }


    public function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? OrderRequestResource::getModernLayout($table)
            : OrderRequestResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
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
                        return OrderRequestResource::getUrl('edit', ['record' => $record->id]);
                    }, shouldOpenInNewTab: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn() => OrderRequestResource::getUrl('create'), shouldOpenInNewTab: true),
            ])
            ->poll(30);
    }
}
