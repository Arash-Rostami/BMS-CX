<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerEntryResource\Pages;
use App\Filament\Resources\LedgerEntryResource\RelationManagers;
use App\Models\LedgerEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('type')
                    ->options([
                        'disbursement' => 'Disbursement',
                        'receipt' => 'Receipt',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required(),
                Forms\Components\TextInput::make('currency_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount_foreign_currency')
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $foreignAmount = (float) $get('amount_foreign_currency');
                        $exchangeRate = (float) $get('exchange_rate');
                        if ($exchangeRate > 0) {
                            $baseAmount = $foreignAmount / $exchangeRate;
                            $set('principal_amount_base', number_format($baseAmount, 3, '.', ''));

                            $commission = (float) $get('commission_amount');
                            $totalDisbursed = $baseAmount + $commission;
                            $set('total_disbursed_base', number_format($totalDisbursed, 3, '.', ''));
                            $set('remaining_principal', number_format($baseAmount, 3, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('exchange_rate')
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $foreignAmount = (float) $get('amount_foreign_currency');
                        $exchangeRate = (float) $get('exchange_rate');
                        if ($exchangeRate > 0) {
                            $baseAmount = $foreignAmount / $exchangeRate;
                            $set('principal_amount_base', number_format($baseAmount, 3, '.', ''));

                            $commission = (float) $get('commission_amount');
                            $totalDisbursed = $baseAmount + $commission;
                            $set('total_disbursed_base', number_format($totalDisbursed, 3, '.', ''));
                            $set('remaining_principal', number_format($baseAmount, 3, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('commission_amount')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $principalBase = (float) $get('principal_amount_base');
                        $commission = (float) $get('commission_amount');
                        $totalDisbursed = $principalBase + $commission;
                        $set('total_disbursed_base', number_format($totalDisbursed, 3, '.', ''));
                    }),
                Forms\Components\TextInput::make('principal_amount_base')
                    ->required()
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('total_disbursed_base')
                    ->required()
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('remaining_principal')
                    ->required()
                    ->numeric()
                    ->readOnly(),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\Hidden::make('rate_matrix_snapshot')
                    ->default(fn () => config('financial.interest_tiers')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable()
                    ->visible(fn () => auth()->user()?->hasRole('admin') ?? false),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('principal_amount_base')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_principal')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unpaid_interest')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_settled')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->visible(fn () => auth()->user()?->hasRole('admin') ?? false),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'disbursement' => 'Disbursement',
                        'receipt' => 'Receipt',
                    ]),
                Tables\Filters\TernaryFilter::make('is_settled'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Contract Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('account.name'),
                        Infolists\Components\TextEntry::make('type'),
                        Infolists\Components\TextEntry::make('description'),
                        Infolists\Components\TextEntry::make('transaction_date')->date(),
                        Infolists\Components\TextEntry::make('currency_type'),
                        Infolists\Components\TextEntry::make('amount_foreign_currency')->numeric(decimalPlaces: 3),
                        Infolists\Components\TextEntry::make('exchange_rate')->numeric(decimalPlaces: 3),
                        Infolists\Components\TextEntry::make('principal_amount_base')->numeric(decimalPlaces: 3),
                        Infolists\Components\TextEntry::make('commission_amount')->numeric(decimalPlaces: 3),
                        Infolists\Components\TextEntry::make('total_disbursed_base')->numeric(decimalPlaces: 3),
                        Infolists\Components\TextEntry::make('remaining_principal')->numeric(decimalPlaces: 3),
                        Infolists\Components\TextEntry::make('unpaid_interest')->numeric(decimalPlaces: 3),
                        Infolists\Components\IconEntry::make('is_settled')->boolean(),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SettlementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerEntries::route('/'),
            'create' => Pages\CreateLedgerEntry::route('/create'),
            'view' => Pages\ViewLedgerEntry::route('/{record}'),
            'edit' => Pages\EditLedgerEntry::route('/{record}/edit'),
        ];
    }
}
