<?php

namespace App\Services;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use SplObjectStorage;


class TableObserver
{
    /**
     * In-memory cache of [key=>[missing,total,filled]] per record
     * @var SplObjectStorage<Model, array<string, array<string,int>>>|null
     */
    private static ?SplObjectStorage $statsCache = null;

    /**
     * Attributes to exclude from counting
     * @var array<string,bool>
     */
    private static array $excluded = [
        'user_id'    => true,
        'extra'      => true,
        'created_at' => true,
        'updated_at' => true,
        'deleted_at' => true,
        'id'         => true,
    ];

    /**
     * Cached fillable fields per class
     * @var array<string, array<string>>
     */
    private static array $fillableCache = [];

    /**
     * Related names for Order
     * @var array<string>
     */
    private const ORDER_RELATIONS = ['orderDetail', 'doc', 'logistic'];

    public static function showMissingData(int $initialCount = 0, ?int $adjustment = null): TextColumn
    {
        return self::makeMissingDataColumn($initialCount, false, $adjustment);
    }

    public static function showMissingDataWithRel(int $initialCount = 0, ?int $adjustment = null): TextColumn
    {
        return self::makeMissingDataColumn($initialCount, true, $adjustment);
    }

    public static function showCompletionPercentage(bool $includeRelations = true, ?int $adjustment = null): TextColumn
    {
        return TextColumn::make('completion_percentage')
            ->label('Completion')
            ->grow(false)
            ->state(function (Model $record) use ($includeRelations, $adjustment) {
                $stats = self::getCompletionStats($record, $includeRelations, $adjustment);
                $percent = $stats['total'] > 0
                    ? (int) round(($stats['filled'] / $stats['total']) * 100)
                    : 100;
                return "{$percent}%";
            })
            ->icon('heroicon-s-chart-bar')
            ->color(fn(string $state) => match (true) {
                (int) rtrim($state, '%') >= 90 => 'success',
                (int) rtrim($state, '%') >= 70 => 'warning',
                default => 'danger',
            })
            ->toggleable(isToggledHiddenByDefault: true)
            ->badge();
    }

    protected static function makeMissingDataColumn(int $initialCount, bool $includeRelations, ?int $adjustment): TextColumn
    {
        return TextColumn::make('missing_data')
            ->label('Missing Info')
            ->grow(false)
            ->state(function (Model $record) use ($initialCount, $includeRelations, $adjustment) {
                $stats = self::getCompletionStats($record, $includeRelations, $adjustment);
                $missing = max(0, $initialCount + $stats['missing']);
                return $missing === 0 ? 'Complete' : "{$missing} Missing";
            })
            ->icon('heroicon-s-puzzle-piece')
            ->color(fn(string $state) => $state === 'Complete' ? 'success' : 'danger')
            ->toggleable(isToggledHiddenByDefault: true)
            ->badge();
    }

    /**
     * @return array<string,int> ['missing'=>..., 'total'=>..., 'filled'=>...]
     */
    protected static function getCompletionStats(Model $record, bool $includeRelations, ?int $adjustment): array
    {
        if (self::$statsCache === null) {
            self::$statsCache = new SplObjectStorage();
        }

        $key = (int) $includeRelations . ':' . ($adjustment ?? 'n');

        // retrieve existing map for this record
        if (self::$statsCache->contains($record)) {
            $map = self::$statsCache->offsetGet($record);
            if (isset($map[$key])) {
                return $map[$key];
            }
        } else {
            $map = [];
        }

        // compute fresh
        [$missing, $total] = self::calculateStats($record);
        if ($adjustment !== null) {
            $total   = max(0, $total - $adjustment);
            $missing = max(0, $missing - $adjustment);
        }
        if ($includeRelations && $record instanceof Order) {
            foreach (self::ORDER_RELATIONS as $rel) {
                if ($model = $record->{$rel}) {
                    [$m2, $t2] = self::calculateStats($model);
                    $missing += $m2;
                    $total   += $t2;
                }
            }
        }
        $filled = max(0, $total - $missing);
        $stats  = ['missing' => $missing, 'total' => $total, 'filled' => $filled];

        // store back
        $map[$key] = $stats;
        self::$statsCache->offsetSet($record, $map);

        return $stats;
    }

    /**
     * @return array<int,int> [missing, total]
     */
    private static function calculateStats(Model $model): array
    {
        $class = get_class($model);
        if (! isset(self::$fillableCache[$class])) {
            self::$fillableCache[$class] = array_values(
                array_diff(
                    $model->getFillable(),
                    array_keys(self::$excluded)
                )
            );
        }
        $attrs = self::$fillableCache[$class];
        $missing = 0;
        foreach ($attrs as $attr) {
            $val = $model->getAttribute($attr);
            if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                $missing++;
            }
        }
        return [$missing, count($attrs)];
    }
}
