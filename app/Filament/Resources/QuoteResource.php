<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Models\DeliveryTerm;
use App\Models\Packaging;
use App\Models\Quote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Operational Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('quote_request_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('transportation_means')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transportation_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('origin_port')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('destination_port')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('offered_rate')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('switch_bl_fee')
                    ->maxLength(255),
                Forms\Components\TextInput::make('commodity_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('packing_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_terms')
                    ->maxLength(255),
                Forms\Components\TextInput::make('free_time_pol')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('free_time_pod')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('validity'),
                Forms\Components\Textarea::make('extra')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quote_provider_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('attachment_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        global $state;
        return $table
            ->columns([
                TextColumn::make('quoteProvider.name')
                    ->label('Quote Provider')
                    ->badge()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('quoteRequest.commodity')
                    ->label('Request for')
                    ->badge()
                    ->color('secondary')
                    ->searchable(['commodity'])
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('commodity_type')
                    ->label('Commodity')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('origin_port')
                    ->label('POL')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('destination_port')
                    ->label('POD')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('transportation_means')
                    ->label('Transportation Means')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('transportation_type')
                    ->label('Transportation Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => DeliveryTerm::find($state)->name)
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('offered_rate')
                    ->label('Offered Rate')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('switch_bl_fee')
                    ->label('Switch BL Fee')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('validity')
                    ->date()
                    ->badge()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('packing_type')
                    ->label('Packing')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->formatStateUsing(fn(string $state) => Packaging::find($state)->name)
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('payment_terms')
                    ->label('Payment Terms')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('free_time_pol')
                    ->label('Free Time')
                    ->badge()
                    ->color('secondary')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                IconColumn::make('attachment.file_path')
                    ->boolean()
                    ->icon(fn(Model $record) => $record->attachment?->file_path ? 'heroicon-s-check-badge' : 'heroicon-o-document-text')
                    ->color(fn(Model $record) => $record->attachment?->file_path ? 'success' : 'gray'),




                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->poll(30)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\QuoteResource\Pages\ListQuotes::route('/'),
            'create' => Operational\QuoteResource\Pages\CreateQuote::route('/create'),
            'view' => Operational\QuoteResource\Pages\ViewQuote::route('/{record}'),
            'edit' => Operational\QuoteResource\Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
