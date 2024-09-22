<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents;

use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

trait Filter
{

    /**
     * @return Grouping
     */
    public static function filterByCurrency(): Grouping
    {
        return Grouping::make('currency')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => data_get($record, 'currency'));
    }

    /**
     * @return Grouping
     */
    public static function filterByTransferringDate(): Grouping
    {
        return Grouping::make('date')
            ->label('Transferring Date')
            ->collapsible()
            ->getKeyFromRecordUsing(function (Model $record): string {
                return $record->date ? (string) $record->date : 'no-date';
            })
            ->getTitleFromRecordUsing(fn(Model $record): string => data_get($record, 'date')
                ? Carbon::parse(data_get($record, 'date'))->format('F j, Y')
                : 'Undefined');
    }



    /**
     * @return Grouping
     */
    public static function filterByPayer(): Grouping
    {
        return Grouping::make('payer')
            ->collapsible();
    }

    /**
     * @return Grouping
     */
    public static function filterByBalance(): Grouping
    {
        return Grouping::make('extra')->collapsible()
            ->label('Balance')
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->extra)['balanceStatus'] ? $record->extra['balanceStatus'] : 'undfined')
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->extra)['balanceStatus'] ? $record->extra['balanceStatus'] : 'undfined');
    }

    /**
     * @return Grouping
     */
    public static function filterByPaymentRequest(): Grouping
    {
        return Grouping::make('paymentRequests.id')->collapsible()
            ->label('Payment Request')
            ->getKeyFromRecordUsing(function (Model $record): string {
                $reasons = $record->paymentRequests->pluck('reason')->pluck('reason');
                return $reasons->first();
            })
            ->getTitleFromRecordUsing(function (Model $record): string {
                $reasons = $record->paymentRequests->pluck('reason')->pluck('reason');
                return ($reasons->unique()->count() === 1) ? $reasons->first() : "Multiple payment requests: " . $reasons->join(', ');
            });
    }
}
