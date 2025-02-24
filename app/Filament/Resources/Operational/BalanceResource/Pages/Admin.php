<?php

namespace App\Filament\Resources\Operational\BalanceResource\Pages;

use App\Models\Balance;
use App\Models\Beneficiary;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BalanceSummarizer;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DbBuilder;
use Illuminate\Database\Eloquent\Model;

class Admin
{
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
                    'payees' => Beneficiary::all()->pluck('name', 'id'),
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
                'payees' => 'Beneficiaries',
            ]);
    }

    /**
     * @return Select
     */
    public static function getDepartment(): Select
    {
        return Select::make('department_id')
            ->label('Department')
            ->required()
            ->options(function () {
                $departments = Department::getAllDepartmentNames();
                unset($departments[0]);
                return $departments;
            })
            ->live();
    }

    /**
     * @return TextInput
     */
    public static function getPayment(): TextInput
    {
        return TextInput::make('payment')
            ->label('Debit (Paid)')
            ->required()
            ->default(0)
            ->numeric();
    }


    /**
     * @return TextInput
     */
    public static function getBase(): TextInput
    {
        return TextInput::make('base')
            ->label('Credit (Receivable)')
            ->default(0)
            ->afterStateHydrated(function (Set $set, Get $get, $state, $record) {
                if ($record && !Balance::isBaseColumnUpdatable(auth()->user())) {
                    $proposedBase = $record->extra['proposed_base'] ?? null;
                    if ($proposedBase) {
                        $set('base', $proposedBase);
                    }
                }
            })
            ->numeric();
    }


    /**
     * @return Select|string|null
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->options(showCurrencies())
            ->required()
            ->label('Currency');
    }

    /**
     * @return TextColumn
     */
    public static function showCurrency(): TextColumn
    {
        return TextColumn::make('currency')
            ->grow(false)
            ->color('secondary')
            ->searchable()
            ->summarize(Count::make()->label('Count'));
    }

    /**
     * @return TextColumn
     */
    public static function showBase(): TextColumn
    {
        return TextColumn::make('base')
            ->label('Credit (Receivable)')
            ->default("N/A")
            ->badge()
            ->formatStateUsing(fn($state) => $state ? number_format($state) : 'N/A')
            ->grow(false)
            ->tooltip(function (Balance $record) {
                if (isset($record->extra['base_approved_at'])) {
                    $approvedAt = Carbon::parse($record->extra['base_approved_at'])
                        ->format('M j, Y g:i A');

                    $approvedBy = User::find($record->extra['base_approved_by'] ?? null);
                    $name = $approvedBy ? $approvedBy->full_name : 'Unknown User';

                    return "Approved on {$approvedAt}\nBy {$name}";
                }

                return 'No Credit or Pending Approval';
            })
            ->color('warning')
            ->numeric()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showPayment(): TextColumn
    {
        return TextColumn::make('payment')
            ->label('Debit (Paid)')
            ->formatStateUsing(fn($state) => $state ? number_format($state) : 'N/A')
            ->grow(false)
            ->tooltip('Payment Sum')
            ->searchable()
            ->sortable()
            ->color('secondary');
//            ->summarize(Sum::make()->label('Payment Sum'));
    }

    /**
     * @return TextColumn
     */
    public static function showDepartment(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label('Department')
            ->grow(false)
            ->searchable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showTotal(): TextColumn
    {
        return TextColumn::make('total')
            ->label('Net Balance')
            ->numeric()
            ->sortable()
            ->badge()
            ->grow(true)
            ->tooltip('Total sum')
            ->color(fn(Model $record) => self::determineColorBasedOnCurrency($record))
            ->state(fn(Model $record) => self::computeTotalBasedOnCurrency($record))
            ->summarize(
                Summarizer::make()
                    ->label('Total')
                    ->using(fn(DbBuilder $query) => BalanceSummarizer::formatSummaryOutput(
                        BalanceSummarizer::summarizeByCurrency($query)
                    ))
            );
    }


    /**
     * @return TextColumn
     */
    public static function showDate(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Total')
            ->tooltip('Total')
            ->numeric()
            ->sortable()
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
            ->grow(false)
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
            ->alignRight()
            ->color('secondary')
            ->searchable(['first_name', 'last_name']);
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
        return Grouping::make('beneficiary.name')
            ->collapsible()
            ->label('Beneficiary')
            ->orderQueryUsing(fn(Builder $query, string $direction) => $query->where('category', 'payees')->orderBy('category_id', $direction))
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->beneficiary?->name ?? 'No beneficiary');
    }

    /**
     * @return Grouping
     */
    public static function groupBySumCurrency(): Grouping
    {
        return Grouping::make('currency')
            ->collapsible()
            ->label('Sum Currency')
            ->getTitleFromRecordUsing(fn(Model $record) => $record->currency ?? 'No currency');
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
//            ->orderQueryUsing(fn(Builder $query, string $direction) => $query->where('category', 'departments')->orderBy('category_id', $direction))
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
                'model' => Beneficiary::class,
                'searchable_columns' => ['name'],
            ],
            'department' => [
                'model' => Department::class,
                'searchable_columns' => ['name', 'code'],
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
            'payees' => Beneficiary::find($state)->name ?? '',
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
        $currency = $record->currency ?? null;

        if (!$currency) {
            return ['total' => 'No currency!', 'color' => 'danger'];
        }

        return ['total' => $record->total, 'color' => 'success'];
    }

    public static function filterByDepartment(): SelectFilter
    {
        return SelectFilter::make('department')
            ->label('Dep: ')
            ->options(Department::getAllDepartmentNames())
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    $query->where('department_id', $data['value']);
                }
            });
    }

    public static function filterByRecipient(): SelectFilter
    {
        return SelectFilter::make('recipient')
            ->label('Recipient')
            ->options(fn() => Balance::getGroupedRecipientOptions())
            ->searchable()
            ->placeholder('Select Recipient')
            ->multiple(false)
            ->query(function (Builder $query, array $data) {
                if (!empty($data['value'])) {
                    if (str_contains($data['value'], '_')) {
                        $parts = explode('_', $data['value'], 2);
                        if (count($parts) == 2) {
                            [$category, $id] = $parts;

                            switch ($category) {
                                case 'supplier':
                                    $query->where('category', 'suppliers')
                                        ->where('category_id', $id);
                                    break;
                                case 'contractor':
                                    $query->where('category', 'contractors')
                                        ->where('category_id', $id);
                                    break;
                                case 'payee':
                                    $query->where('category', 'payees')
                                        ->where('category_id', $id);
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }

            });
    }
}
