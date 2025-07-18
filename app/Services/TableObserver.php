<?php

namespace App\Services;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class TableObserver
{

    protected static array $excludedAttributes = [
        'user_id', 'extra', 'created_at', 'updated_at', 'deleted_at', 'id'
    ];

    public static function showMissingData(int $initialCount = 0, ?int $adjustment = null): TextColumn
    {
        return self::makeMissingDataColumn($initialCount, false, $adjustment);
    }
    public static function showMissingDataWithRel(int $initialCount = 0, ?int $adjustment = null): TextColumn
    {
        return self::makeMissingDataColumn($initialCount, true, $adjustment);
    }
    protected static function makeMissingDataColumn(int $initialCount, bool $includeRelations, ?int $adjustment = null): TextColumn
    {
        return TextColumn::make('missing_data')
            ->label('Missing Info')
            ->grow(false)
            ->state(function (Model $record) use ($initialCount, $includeRelations, $adjustment) {
                $missingCount = $initialCount + self::countMissingData($record, $includeRelations, $adjustment);
                return $missingCount <= 0 ? 'Complete' : "{$missingCount} Missing";
            })
            ->icon('heroicon-s-puzzle-piece')
            ->color(fn($state) => $state === 'Complete' ? 'success' : 'danger')
            ->toggleable(isToggledHiddenByDefault: true)
            ->badge();
    }
    public static function showCompletionPercentage(bool $includeRelations = true, ?int $adjustment = null): TextColumn
    {
        return TextColumn::make('completion_percentage')
            ->label('Completion')
            ->grow(false)
            ->state(function (Model $record) use ($includeRelations, $adjustment) {
                [$filled, $total] = self::getCompletionStats($record, $includeRelations, $adjustment);
                $percentage = $total > 0 ? round(($filled / $total) * 100) : 100;
                return "{$percentage}%";
            })
            ->icon('heroicon-s-chart-bar')
            ->color(function ($state) {
                $percentage = (int)str_replace('%', '', $state);
                return match (true) {
                    $percentage >= 90 => 'success',
                    $percentage >= 70 => 'warning',
                    default => 'danger'
                };
            })
            ->toggleable(isToggledHiddenByDefault: true)
            ->badge();
    }

    /**
     * Count missing data for a record
     */
    protected static function countMissingData(Model $record, bool $includeRelations = true, ?int $adjustment = null): int
    {
        $count = 0;

        // Count main model
        $count += self::countMissingInModel($record);

        // Apply  adjustment if specified
        if ($adjustment !== null) {
            $count = max(0, $count - $adjustment);
        }

        // Only Order gets related models when includeRelations is true
        if ($includeRelations && $record instanceof Order) {
            $count += self::countOrderRelatedMissing($record);
        }

        return $count;
    }

    /**
     * Count missing attributes in a single model
     */
    protected static function countMissingInModel(Model $model): int
    {
        $fillable = $model->getFillable();
        $attributes = $model->getAttributes();
        $count = 0;

        foreach ($fillable as $attribute) {
            if (in_array($attribute, self::$excludedAttributes)) {
                continue;
            }

            $value = $attributes[$attribute] ?? null;

            if (is_null($value) || $value === '' || $value === []) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count missing data in Order related models (OrderDetail, Doc, Logistic)
     */
    protected static function countOrderRelatedMissing(Order $order): int
    {
        $count = 0;

        // Count order details
        if ($order->orderDetail) {
            $count += self::countMissingInModel($order->orderDetail);
        }

        // Count doc
        if ($order->doc) {
            $count += self::countMissingInModel($order->doc);
        }

        // Count logistic
        if ($order->logistic) {
            $count += self::countMissingInModel($order->logistic);
        }

        return $count;
    }

    /**
     * Get completion statistics [filled, total]
     */
    protected static function getCompletionStats(Model $record, bool $includeRelations, ?int $adjustment = null): array
    {
        $totalFields = self::countTotalFields($record, $includeRelations, $adjustment);
        $missingFields = self::countMissingData($record, $includeRelations, $adjustment);
        $filledFields = $totalFields - $missingFields;

        return [$filledFields, $totalFields];
    }

    /**
     * Count total fillable fields across all models
     */
    protected static function countTotalFields(Model $record, bool $includeRelations, ?int $adjustment = null): int
    {
        $count = count(array_diff($record->getFillable(), self::$excludedAttributes));

        // Apply PaymentRequest adjustment to total fields if specified
        if ($adjustment !== null) {
            $count = max(0, $count - $adjustment);
        }

        // Only Order gets related model field counts
        if ($includeRelations && $record instanceof Order) {
            $count += self::countOrderTotalFields($record);
        }

        return $count;
    }

    /**
     * Count total fields in Order related models (OrderDetail, Doc, Logistic)
     */
    protected static function countOrderTotalFields(Order $order): int
    {
        $count = 0;

        if ($order->orderDetail) {
            $count += count(array_diff($order->orderDetail->getFillable(), self::$excludedAttributes));
        }

        if ($order->doc) {
            $count += count(array_diff($order->doc->getFillable(), self::$excludedAttributes));
        }

        if ($order->logistic) {
            $count += count(array_diff($order->logistic->getFillable(), self::$excludedAttributes));
        }

        return $count;
    }
}
