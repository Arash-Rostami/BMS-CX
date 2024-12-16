<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BalanceResource\Pages;
use App\Filament\Resources\BalanceResource\RelationManagers;
use App\Filament\Resources\Operational\BalanceResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Models\Balance;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class BalanceResource extends Resource
{
    protected static ?string $model = Balance::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 6;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getCurrency(),
                Admin::getBase(),
                Admin::getPayment(),
                Admin::getDepartment(),
                Admin::getCategory(),
                Admin::getRecipient(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->filterByUserDepartment(auth()->user()))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->defaultSort('created_at', 'desc')
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultGroup('department.code')
            ->groups([
                Admin::groupByCategory(),
                Admin::groupByPayee(),
                Admin::groupBySupplier(),
                Admin::groupByContractor(),
                Admin::groupByDepartment(),
                Admin::groupBySumCurrency(),
            ])
            ->paginated([10, 20, 30])
            ->filters([Admin::filterByDepartment(), AdminOrder::filterCreatedAt()])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([]),
            ]);
    }


    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Stack::make([
                            Split::make([
                                Admin::showCurrency(),
                                Admin::showBase(),
                                Admin::showPayment(),
                                Admin::showTotal(),
                                Admin::showUser(),
                            ]),
                            Split::make([
                                Admin::showDepartment(),
                                Admin::showRecipient(),
                                Admin::showTimeStamp(),
                            ]),
                        ])->space(2),
                    ])
                ])->columnSpanFull(),
                Admin::showTimeStamp()
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showDepartment(),
                Admin::showCurrency(),
                Admin::showBase(),
                Admin::showPayment(),
                Admin::showTotal(),
                Admin::showRecipient(),
                Admin::showUser(),
                Admin::showTimeStamp(),
            ])->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\BalanceResource\Pages\ManageBalances::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = static::getModel()::query()
            ->filterByUserDepartment($user)
            ->count();

        return (string) $count;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
