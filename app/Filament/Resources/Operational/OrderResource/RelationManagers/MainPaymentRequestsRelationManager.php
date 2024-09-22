<?php

namespace App\Filament\Resources\Operational\OrderResource\RelationManagers;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MainPaymentRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'associatedPaymentRequests';

    protected static ?string $title = 'Payment Requests ( PI ⭐)';


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
                        return PaymentRequestResource::getUrl('edit', ['record' => $record->id]);
                    }, shouldOpenInNewTab: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn() => PaymentRequestResource::getUrl('create'), shouldOpenInNewTab: true),
            ])
            ->poll(30);
    }
}
