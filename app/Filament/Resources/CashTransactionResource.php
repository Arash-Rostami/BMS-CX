<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\CashTransactionResource\Pages\Admin;
use App\Filament\Resources\Operational\CashTransactionResource\RelationManagers;
use App\Models\CashTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class CashTransactionResource extends Resource
{

    protected static ?string $model = CashTransaction::class;

    protected static ?string $pluralModelLabel = 'Cash Ledger';

    protected static ?string $navigationLabel = 'Cash Ledger';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 8;

    protected static ?string $pollingInterval = null;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Admin::getAmount(),
            Admin::getCurrency(),
            Admin::getDescription()
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user',
                'payment'
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return 'New';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\CashTransactionResource\Pages\ManageCashTransactions::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('No transactions yet. Add funds or your first cash payment to start tracking.')
            ->columns([
                Admin::showType(),
                Admin::showAmount(),
                Admin::showPayment(),
                Admin::showDescription(),
                Admin::showUser(),
                Admin::showTimestamp(),
            ])
            ->poll('900s')
            ->defaultSort('created_at', 'desc')
            ->actions([ActionGroup::make([EditAction::make(), DeleteAction::make()])])
            ->bulkActions([]);
    }
}
