<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettlementResource\Pages;
use App\Models\Settlement;
use App\Models\Account;
use App\Jobs\RecalculateAccountLedger;
use App\Services\InterestCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')
                    ->label('Account')
                    ->options(Account::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required(),
                Forms\Components\TextInput::make('foreign_settlement_amount')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $foreignAmount = (float) $get('foreign_settlement_amount');
                        $exchangeRate = (float) $get('settlement_exchange_rate');
                        if ($exchangeRate > 0) {
                            $baseAmount = $foreignAmount / $exchangeRate;
                            $set('settlement_amount', number_format($baseAmount, 3, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('settlement_exchange_rate')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $foreignAmount = (float) $get('foreign_settlement_amount');
                        $exchangeRate = (float) $get('settlement_exchange_rate');
                        if ($exchangeRate > 0) {
                            $baseAmount = $foreignAmount / $exchangeRate;
                            $set('settlement_amount', number_format($baseAmount, 3, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('settlement_amount')
                    ->required()
                    ->numeric()
                    ->readOnly(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ledgerEntry.account.name')
                    ->label('Account')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('settlement_amount')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('deducted_from_interest')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('deducted_from_principal')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_id')
                    ->label('Account')
                    ->relationship('ledgerEntry.account', 'name'),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Edit Past Settlement')
                    ->modalDescription('Are you sure you want to edit this settlement? This will trigger a synchronous chronological recalculation of all subsequent ledger entries and settlements for this account.')
                    ->modalSubmitActionLabel('Yes, edit and recalculate')
                    ->using(function (Model $record, array $data): Model {
                        $accountId = $record->ledgerEntry->account_id;
                        $oldDate = $record->getOriginal('transaction_date');
                        $newDate = $data['transaction_date'];

                        $recalcDate = $oldDate < $newDate ? $oldDate : $newDate;

                        $record->update($data);

                        RecalculateAccountLedger::dispatchSync($accountId, $recalcDate);

                        Notification::make()
                            ->title('Ledger timeline successfully recalculated')
                            ->success()
                            ->send();

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Past Settlement')
                    ->modalDescription('Are you sure you want to delete this settlement? This will trigger a synchronous chronological recalculation of all subsequent ledger entries and settlements for this account.')
                    ->modalSubmitActionLabel('Yes, delete and recalculate')
                    ->action(function (Model $record) {
                        $accountId = $record->ledgerEntry->account_id;
                        $recalcDate = $record->transaction_date;

                        $record->delete();

                        RecalculateAccountLedger::dispatchSync($accountId, $recalcDate);

                        Notification::make()
                            ->title('Ledger timeline successfully recalculated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
            'create' => Pages\CreateSettlement::route('/create'),
        ];
    }
}
