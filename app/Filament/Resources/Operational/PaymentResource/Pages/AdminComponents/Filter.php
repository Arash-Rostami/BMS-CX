<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents;

use App\Models\Allocation;
use App\Models\Department;
use App\Models\Payment;
use App\Models\PaymentRequest;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
                return $record->date ? (string)$record->date : 'no-date';
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
        return Grouping::make('payment_request')
            ->collapsible()
            ->getKeyFromRecordUsing(function (Model $record) {
                if (empty($record->payment_request)) {
                    return 'N/A';
                }
                $refNumbers = explode(',', $record->payment_request);
                sort($refNumbers);
                return implode(', ', $refNumbers);
            })
            ->getTitleFromRecordUsing(function (Model $record) {
                if (empty($record->payment_request)) {
                    return 'N/A';
                }
                $refNumbers = explode(',', $record->payment_request);
                sort($refNumbers);
                return implode(' ⚝ ', $refNumbers);
            })
            ->label('Payment Request');
    }


    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterReason(): SelectFilter
    {
        return SelectFilter::make('reason')
            ->label('Reasons')
            ->options(Cache::remember('reason_options', 60, fn() => Allocation::orderBy('reason')->pluck('reason', 'id')->toArray()))
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->whereHas('paymentRequests', function (Builder $query) use ($data) {
                        $query->where('reason_for_payment', $data['value']);
                    });
                }
            });
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterDepartments(): SelectFilter
    {
        return SelectFilter::make('department')
            ->label('Department')
            ->options(Cache::remember('department_option_filter', 60, fn() => Department::getAllDepartmentNames()))
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->whereHas('paymentRequests', function (Builder $query) use ($data) {
                        $query->where('department_id', $data['value']);
                    });
                }
            });
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterCostCenter(): SelectFilter
    {
        return SelectFilter::make('costCenter')
            ->label('Cost Center')
            ->options(Cache::remember('department_option_filter', 60, fn() => Department::getAllDepartmentNames()))
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->whereHas('paymentRequests', function (Builder $query) use ($data) {
                        $query->where('cost_center', $data['value']);
                    });
                }
            });
    }

    public static function filterByPRCurrency(): SelectFilter
    {
        return SelectFilter::make('currency')
            ->label('Currency')
            ->options(Payment::getCurrencyOptions())
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->where('currency', $data['value']);
                }
            });
    }


    public static function filterMadeBy(): SelectFilter
    {
        return SelectFilter::make('made_by')
            ->label('Made By')
            ->options(PaymentRequest::getMadeByOptions())
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->whereHas('paymentRequests', function (Builder $query) use ($data) {
                        $query->whereJsonContains('extra->made_by', $data['value']);
                    });
                }
            });
    }
}
