<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\PaymentRequestResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentRequests';

    protected static ?string $title = 'Payment Requests ( Orders 🛒)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
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
            ->searchable(false)
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultGroup('reason_for_payment')
            ->filters([
                AdminOrder::filterCreatedAt(),
                Admin::filterByCurrency(),
                Admin::filterByTypeOfPayment(),
                AdminOrder::filterSoftDeletes(),
            ])
            ->groups([
                Admin::groupByOrder(),
                Admin::groupByReason(),
                Admin::groupByType(),
                Admin::groupByCurrency(),
                Admin::groupByContractor(),
                Admin::groupBySupplier(),
                Admin::groupByBeneficiary(),
                Admin::groupByStatus(),
                Admin::groupByCase(),
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
            ->searchDebounce(5000)
            ->poll(30);
    }
}
