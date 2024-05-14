<?php

namespace App\Filament\Resources\Operational\BalanceResource\Pages;

use App\Models\Contractor;
use App\Models\Department;
use App\Models\Payee;
use App\Models\Supplier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Range;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DbBuilder;
use Illuminate\Database\Eloquent\Model;

class Admin
{

    /**
     * @return Select
     */
    public static function getRecipient(): Select
    {
        return Select::make('category_id')
            ->required()
            ->label('Party')
            ->options(function (Get $get) {
                $category = $get('category');
                $list = [
                    'suppliers' => Supplier::all()->pluck('name', 'id'),
                    'contractors' => Contractor::all()->pluck('name', 'id'),
                    'payees' => Payee::all()->pluck('name', 'id'),
                    'departments' => Department::all()->pluck('name', 'id'),
                ];

                return $category ? $list[$category] : [];
            });
    }

    /**
     * @return Select
     */
    public static function getCategory(): Select
    {
        return Select::make('category')
            ->required()
            ->live()
            ->options([
                'suppliers' => 'Suppliers',
                'contractors' => 'Contractors',
                'payees' => 'Payees',
                'departments' => 'Departments',
            ]);
    }

    /**
     * @return TextInput
     */
    public static function getAmount(): TextInput
    {
        return TextInput::make('amount')
            ->required()
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getInitial(): TextInput
    {
        return TextInput::make('initial')
            ->numeric();
    }


    /**
     * @return Select|string|null
     */
    public static function getInitialCurrency(): Select
    {
        return Select::make('extra.initialCurrency')
            ->options(showCurrencies())
            ->label('Initial Currency');
    }

    /**
     * @return Select|string|null
     */
    public static function getSumCurrency(): Select
    {
        return Select::make('extra.currency')
            ->options(showCurrencies())
            ->required()
            ->label('Currency');
    }

    /**
     * @return TextColumn
     */
    public static function showInitialCurrency(): TextColumn
    {
        return TextColumn::make('extra.initialCurrency')
            ->label('Initial Currency')
            ->grow(false)
            ->color('secondary')
            ->searchable(['extra->initialCurrency'])
            ->formatStateUsing(fn($state) => !is_null($state) ? (showCurrencyWithoutHTMLTags($state)) : 'N/A');
    }

    /**
     * @return TextColumn
     */
    public static function showInitial(): TextColumn
    {
        return TextColumn::make('initial')
            ->default("N/A")
            ->badge()
            ->tooltip('Initial')
            ->color('warning')
            ->numeric()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showSumCurrency(): TextColumn
    {
        return TextColumn::make('extra.currency')
            ->label('Sum Currency')
            ->grow(false)
            ->searchable(['extra->currency'])
            ->color('secondary')
            ->formatStateUsing(fn($state) => !is_null($state) ? (showCurrencyWithoutHTMLTags($state)) : '');
    }

    /**
     * @return TextColumn
     */
    public static function showAmount(): TextColumn
    {
        return TextColumn::make('amount')
            ->numeric()
            ->sortable()
            ->badge()
            ->tooltip('Amount')
            ->summarize(self::totalAndCount());
    }


    /**
     * @return TextColumn
     */
    public static function showTotal(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Total')
            ->tooltip('Total')
            ->numeric()
            ->sortable()
            ->color(fn(Model $record) => self::determineColorBasedOnCurrency($record))
            ->formatStateUsing(fn(Model $record) => self::computeTotalBasedOnCurrency($record))
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showRecipient(): TextColumn
    {
        return TextColumn::make('category_id')
            ->label('Recipient')
            ->badge()
            ->color('secondary')
            ->formatStateUsing(fn(Model $record, $state) => self::showBasedOnModel($record, $state))
            ->searchable(query: fn(Builder $query, string $search) => self::searchAllModels($query, $search))
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showUser(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->label('Made by')
            ->badge()
            ->color('secondary')
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }


    /**
     * @return Grouping
     */
    public static function groupByCategory(): Grouping
    {
        return Grouping::make('category')
            ->label('Category')
            ->collapsible();
    }

    /**
     * @return Grouping
     */
    public static function groupByPayee(): Grouping
    {
        return Grouping::make('payee.name')
            ->collapsible()
            ->label('Payee')
            ->orderQueryUsing(fn(Builder $query, string $direction) => $query->where('category', 'payees')->orderBy('category_id', $direction))
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->payee->name ?? 'No payee');
    }

    /**
     * @return Grouping
     */
    public static function groupBySumCurrency(): Grouping
    {
        return Grouping::make('extra')
            ->collapsible()
            ->label('Sum Currency')
            ->getKeyFromRecordUsing(fn(Model $record) => $record->extra['currency'])
            ->getTitleFromRecordUsing(fn(Model $record) => $record->extra['currency'] ?? 'No currency');
    }

    /**
     * @return Grouping
     */
    public static function groupBySupplier(): Grouping
    {
        return Grouping::make('supplier.name')
            ->orderQueryUsing(fn(Builder $query, string $direction) => $query->where('category', 'suppliers')->orderBy('category_id', $direction))
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->supplier->name ?? 'No supplier');
    }

    /**
     * @return Grouping
     */
    public static function groupByContractor(): Grouping
    {
        return Grouping::make('contractor.name')
            ->orderQueryUsing(fn(Builder $query, string $direction) => $query->where('category', 'contractors')->orderBy('category_id', $direction))
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->contractor->name ?? 'No contractor');
    }


    /**
     * @return Grouping
     */
    public static function groupByDepartment(): Grouping
    {
        return Grouping::make('department.name')
            ->orderQueryUsing(fn(Builder $query, string $direction) => $query->where('category', 'departments')->orderBy('category_id', $direction))
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->department->name ?? 'No department');
    }


    private static function getTables(): array
    {
        return [
            'supplier' => [
                'model' => Supplier::class,
                'searchable_columns' => ['name'],
            ],
            'contractor' => [
                'model' => Contractor::class,
                'searchable_columns' => ['name'],
            ],
            'payee' => [
                'model' => Payee::class,
                'searchable_columns' => ['name'],
            ],
            'department' => [
                'model' => Department::class,
                'searchable_columns' => ['name'],
            ],
        ];
    }

    /**
     * @param Model $record
     * @param $state
     * @return string
     */
    private static function showBasedOnModel(Model $record, $state): string
    {
        $category = $record->category;

        $data = match ($category) {
            'suppliers' => Supplier::find($state)->name ?? '',
            'contractors' => Contractor::find($state)->name ?? '',
            'payees' => Payee::find($state)->name ?? '',
            'departments' => Department::getByCode($state) ?? '',
        };

        return $category ? ucwords($category) . ': ' . $data : '';
    }

    /**
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    private static function searchAllModels(Builder $query, string $search): Builder
    {
        $searchableTables = self::getTables();

        foreach ($searchableTables as $category => $config) {
            $columns = $config['searchable_columns'];

            $query->orWhereHas($category, function (Builder $subQuery) use ($search, $columns) {
                foreach ($columns as $column) {
                    $subQuery->where($column, 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }

    private static function computeTotalBasedOnCurrency($record)
    {
        $currencyComparisonResult = self::determineCurrencyComparisonResult($record);
        return $currencyComparisonResult['total'];
    }

    private static function determineColorBasedOnCurrency($record)
    {
        $currencyComparisonResult = self::determineCurrencyComparisonResult($record);
        return $currencyComparisonResult['color'];
    }

    private static function determineCurrencyComparisonResult($record)
    {
        $initialCurrency = $record->extra['initialCurrency'] ?? null;
        $currency = $record->extra['currency'] ?? null;

        if (!$initialCurrency || !$currency) {
            return ['total' => 'No initial', 'color' => 'secondary'];
        }

        if ($initialCurrency === $currency) {
            return ['total' => $record->initial + $record->amount, 'color' => 'success'];
        }

        return ['total' => 'Conflicting currencies', 'color' => 'danger'];
    }

    private static function totalAndCount()
    {
        $categories = ['contractors', 'departments', 'payees', 'suppliers'];

        // individual sum
        $summarizations = array_map(function ($category) {
            return Sum::make()
                ->label(ucfirst($category) . ' Total')
                ->query(fn(DbBuilder $query) => $query->where('category', $category));
        }, $categories);

        // total count
        $summarizations[] = Count::make()
            //exclude departments for count as it is similar to payees
            ->query(fn(DbBuilder $query) => $query->whereIn('category', array_diff($categories, ['departments'])));

        return $summarizations;
    }
}
