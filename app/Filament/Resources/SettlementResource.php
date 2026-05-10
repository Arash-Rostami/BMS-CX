<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Financial\SettlementResource\Pages\Admin;
use App\Filament\Resources\Financial\SettlementResource\Pages\CreateSettlement;
use App\Filament\Resources\Financial\SettlementResource\Pages\ListSettlements;
use App\Models\Settlement;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'Loan Settlement';
    protected static ?string $modelLabel = 'Loan Settlement';
    protected static ?string $pluralModelLabel = 'Loan Settlements';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Settlement Details')
                        ->schema([
                            Admin::getAccount(),
                            Admin::getCurrencyType(),
                            Admin::getTransactionDate(),
                        ])
                        ->columns(3)
                        ->collapsible(),
                    Section::make('Settlement Amounts')
                        ->schema([
                            Admin::getForeignSettlementAmount(),
                            Admin::getSettlementExchangeRate(),
                            Admin::getSettlementAmount(),
                        ])
                        ->columns(3)
                        ->description('Provide foreign amount and rate to auto-calculate base, or enter base directly for base-currency settlements.'),
                ])->columnSpanFull(),
                // ==========  ATTACHMENTS  ==========
                Section::make('Attachments')
                    ->schema([
                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->hiddenLabel()
                            ->schema([
                                Hidden::make('id'),
                                Admin::getFileUpload(),
                                Admin::getAttachmentTitle(),
                            ])
                            ->defaultItems(0)
                            ->columns(2)
                            ->addActionLabel('➕')
                            ->columnSpanFull()
                            ->collapsible()
                            ->deletable()
                            ->collapsed(),
                    ])->columns(1),
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table->columns([
            Admin::showAccount(),
            Admin::showTransactionDate(),
            Admin::showSettlementAmount(),
            Admin::showDeductedFromInterest(),
            Admin::showDeductedFromPrincipal(),
            Admin::showCounterInterestApplied(),
        ])->striped();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'attachments',
                'ledgerEntry',
                'ledgerEntry.account',
            ]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showAccount(),
                        Admin::showTransactionDate(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showCurrencyType(),
                        Admin::showSettlementAmount(),
                        Admin::showDeductedFromInterest(),
                        Admin::showDeductedFromPrincipal(),
                        Admin::showCounterInterestApplied(),
                    ])->columnSpanFull(true),

                ])
                    ->collapsed(false)
                    ->collapsible(),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('nav.second_category');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettlements::route('/'),
            'create' => CreateSettlement::route('/create'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Settlement Details')
                ->schema([
                    Admin::viewAccount(),
                    Admin::viewTransactionDate(),
                ])
                ->columns(2)
                ->collapsible(),
            InfoSection::make('Settlement Amounts')
                ->schema([
                    Admin::viewCurrencyType(),
                    Admin::viewForeignAmount(),
                    Admin::viewExchangeRate(),
                    Admin::viewSettlementAmount(),
                    Admin::viewDeductedFromInterest(),
                    Admin::viewDeductedFromPrincipal(),
                    Admin::viewCounterInterestApplied(),
                ])
                ->columnSpanFull()
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

    private static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showAccount(),
                Admin::showTransactionDate(),
                Admin::showSettlementAmount(),
                Admin::showDeductedFromInterest(),
                Admin::showDeductedFromPrincipal(),
            ])
            ->filters([
                Admin::filterAccount(),
                Admin::filterTransactionDate(),
            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::ExtraLarge)
            ->filtersFormColumns(2)
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalWidth(MaxWidth::FourExtraLarge),
                    EditAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Edit Past Settlement')
                        ->modalDescription('This will trigger a synchronous chronological recalculation of all subsequent ledger entries and settlements for the account attached.')
                        ->modalSubmitActionLabel('Yes, edit and recalculate')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->using(fn(Model $record, array $data) => Admin::updateAndRecalculate($record, $data)),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Past Settlement')
                        ->modalDescription('This will trigger a synchronous chronological recalculation of all subsequent ledger entries and settlements for the account attached.')
                        ->modalSubmitActionLabel('Yes, delete and recalculate')
                        ->modalWidth(MaxWidth::Large)
                        ->action(fn(Model $record) => Admin::deleteAndRecalculate($record)),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()->exports([ExcelExport::make()->fromTable()])
                ])
            ])
            ->groups([
                Admin::groupCurrencyType(),
                Admin::groupAccountRecords(),
                Admin::groupTransactionDateRecords(),
            ])
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultSort('transaction_date', 'desc')
            ->paginated([15, 30, 50]);
    }
}
