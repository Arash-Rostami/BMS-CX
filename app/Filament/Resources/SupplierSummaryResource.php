<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\SupplierSummaryResource\Pages\Admin;
use App\Models\SupplierSummary;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;

class SupplierSummaryResource extends Resource
{
    protected static ?string $model = SupplierSummary::class;

    protected static ?string $modelLabel = 'Supplier Balance';

    protected static ?string $pluralModelLabel = 'Supplier Balances';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 7;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getSupplier(),
                Admin::getType(),
                Admin::getCurrency(),
                Admin::getDifference(),
                Admin::gtStatus(),
                Admin::getContractNumber(),
                Admin::getPaidAmount(),
                Admin::getExpectedAmount(),
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
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->defaultSort('created_at', 'desc')
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultGroup('supplier.name')
            ->paginated([10, 20, 30])
            ->filters([Admin::filterByProformaInvoice(), Admin::filterBySupplier(), Admin::filterByAdjustment()])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(fn(SupplierSummary $record): bool => $record->type === 'adjustment'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(SupplierSummary $record): bool => $record->type === 'adjustment'),
                ])
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showSupplier(),
                        Admin::showContractNumber(),
                        Admin::showBalance(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showCurrency(),
                        Admin::showPaid(),
                        Admin::showExpected(),
                        Admin::showStatus(),
                        Admin::showTimeStamp(),
                    ])->columnSpanFull(true),
                ])->collapsible(),
            ]);

    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showSupplier(),
                Admin::showContractNumber(),
                Admin::showCurrency(),
                Admin::showPaid(),
                Admin::showExpected(),
                Admin::showBalance(),
                Admin::showStatus(),
                Admin::showTimeStamp(),
            ])->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\SupplierSummaryResource\Pages\ManageSupplierSummaries::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !isSimpleSidebar();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::distinct('supplier_id')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }
}
