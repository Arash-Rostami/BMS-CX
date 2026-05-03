<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Financial\LedgerEntryResource\Pages\Admin;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\CreateLedgerEntry;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\EditLedgerEntry;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\ListLedgerEntries;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\ViewLedgerEntry;
use App\Filament\Resources\Financial\LedgerEntryResource\RelationManagers\SettlementsRelationManager;
use App\Models\LedgerEntry;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';
    protected static ?string $navigationLabel = 'Loan Ledger';
    protected static ?string $modelLabel = 'Loan Ledger';


    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([
                Admin::filterAccount(),
                Admin::filterUser(),
                Admin::filterType(),
                Admin::filterTransactionDate(),
                Admin::filterIsSettled(),
            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::TwoExtraLarge)
            ->filtersFormColumns(2)
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()->exports([ExcelExport::make()->fromTable()])
                ])
            ])
            ->groups([
                Admin::groupAccountRecords(),
                Admin::groupTypeRecords(),
                Admin::groupTransactionDateRecords(),
                Admin::groupSettledRecords(),
                Admin::groupUserRecords(),
            ])
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultSort('transaction_date', 'desc')
            ->paginated([15, 30, 50]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Loan Contract')
                        ->schema([
                            Admin::getAccount(),
                            Admin::getType(),
                            Admin::getDescription(),
                            Admin::getTransactionDate(),
                            Admin::getCurrencyType(),
                            Admin::getUserId(),
                            Admin::getRateMatrixSnapshot(),
                        ])
                        ->columns(3)
                        ->collapsible(),
                    Section::make('Financial Breakdown')
                        ->schema([
                            Admin::getAmountForeignCurrency(),
                            Admin::getExchangeRate(),
                            Admin::getCommissionAmount(),
                            Admin::getPrincipalAmountBase(),
                            Admin::getTotalDisbursedBase(),
                            Admin::getRemainingPrincipal(),
                        ])
                        ->columns(3)
                        ->description('⚡Readonly fields are auto-calculated.'),
                ])->columnSpan(3),
            ])
            ->columns(3);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showAccount(),
                Admin::showType(),
                Admin::showTransactionDate(),
                Admin::showPrincipalAmountBase(),
                Admin::showRemainingPrincipal(),
                Admin::showUnpaidInterest(),
                Admin::showIsSettled(),
                Admin::showCreator(),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'account',
                'settlements',
                'user',
            ]);

        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showAccount(),
                        Admin::showType(),
                        Admin::showDescription(),
                        Admin::showIsSettled(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showTransactionDate(),
                        Admin::showPrincipalAmountBase(),
                        Admin::showRemainingPrincipal(),
                        Admin::showUnpaidInterest(),
                    ])->columnSpanFull(true),
                    Admin::showCreator(),

                ])->collapsible(),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('nav.second_category');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerEntries::route('/'),
            'create' => CreateLedgerEntry::route('/create'),
            'view' => ViewLedgerEntry::route('/{record}'),
            'edit' => EditLedgerEntry::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [SettlementsRelationManager::class];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Loan Contract')
                ->schema([
                    Admin::viewAccount(),
                    Admin::viewType(),
                    Admin::viewDescription(),
                    Admin::viewTransactionDate(),
                    Admin::viewCurrencyType(),
                    Admin::viewIsSettled(),
                ])
                ->columns(3)
                ->collapsible(),
            InfoSection::make('Financial Breakdown')
                ->schema([
                    Admin::viewAmountForeignCurrency(),
                    Admin::viewExchangeRate(),
                    Admin::viewCommissionAmount(),
                    Admin::viewPrincipalAmountBase(),
                    Admin::viewTotalDisbursedBase(),
                    Admin::viewRemainingPrincipal(),
                    Admin::viewUnpaidInterest(),
                    Admin::viewCreator(),
                ])
                ->columns(3)
                ->collapsible(),
        ]);
    }


    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);
    }
}
