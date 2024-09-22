<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MainPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentRequests';

    protected static ?string $title = 'Payments ( PI ⭐)';

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
        $ownRecord = ($this->ownerRecord);
        $table = self::configureCommonTableSettings($table, $ownRecord);

        return (getTableDesign() != 'classic')
            ? PaymentResource::getModernLayout($table)
            : PaymentResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table, $ownRecord): Table
    {
        return $table
            ->query(function () use ($ownRecord) {
                $proformaInvoiceId = $ownRecord->id;
                return Payment::from(DB::raw("(
                        SELECT DISTINCT p.*
                        FROM proforma_invoices pi
                        JOIN payment_request_proforma_invoice pri ON pi.id = pri.proforma_invoice_id
                        JOIN payment_requests pr ON pri.payment_request_id = pr.id AND pr.deleted_at IS NULL
                        JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
                        JOIN payments p ON ppr.payment_id = p.id AND p.deleted_at IS NULL
                        WHERE pi.id = {$proformaInvoiceId} AND pi.deleted_at IS NULL
                    ) AS payments"));
            })
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
                        return PaymentResource::getUrl('edit', ['record' => $record->id]);
                    }, shouldOpenInNewTab: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn() => PaymentResource::getUrl('create'), shouldOpenInNewTab: true),
            ])
            ->poll(30);
    }
}
