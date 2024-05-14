<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BalanceResource\Pages;
use App\Filament\Resources\BalanceResource\RelationManagers;
use App\Filament\Resources\Operational\BalanceResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Models\Balance;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Grouping\Group as Grouping;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class BalanceResource extends Resource
{
    protected static ?string $model = Balance::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Operational Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getInitialCurrency(),
                Admin::getInitial(),
                Admin::getSumCurrency(),
                Admin::getAmount(),
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
            ->defaultSort('created_at', 'desc')
            ->poll(60)
            ->groups([
                Admin::groupByCategory(),
                Admin::groupByPayee(),
                Admin::groupBySupplier(),
                Admin::groupByContractor(),
                Admin::groupByDepartment(),
                Admin::groupBySumCurrency(),
            ])
            ->filters([AdminOrder::filterCreatedAt()])
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
                                Admin::showInitialCurrency(),
                                Admin::showInitial(),
                                Admin::showRecipient(),
                            ]),
                            Split::make([
                                Admin::showSumCurrency(),
                                Admin::showAmount(),
                                Admin::showUser(),
                            ]),
                            Admin::showTotal(),
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
                Admin::showInitialCurrency(),
                Admin::showInitial(),
                Admin::showSumCurrency(),
                Admin::showAmount(),
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
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
