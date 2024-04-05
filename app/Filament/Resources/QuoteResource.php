<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\QuoteResource\Pages\Admin;
use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Models\DeliveryTerm;
use App\Models\Packaging;
use App\Models\Quote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Operational Data';


    public static function form(Form $form): Form
    {
        return $form->schema([]);
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
            ->filters([Admin::filterCreatedAt(), Admin::filterSoftDeletes()])
            ->poll(30)
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showQuoteProvider(),
                Admin::showCommodity(),
                Admin::showCommodityType(),
                Admin::showOriginPort(),
                Admin::showDestinationPort(),
                Admin::showTransportationMeans(),
                Admin::showTransportationType(),
                Admin::showOfferedRate(),
                Admin::showSwitchBLFee(),
                Admin::showValidity(),
                Admin::showPackingType(),
                Admin::showPaymentTerms(),
                Admin::showFreeTime(),
                Admin::showAttachment(),
                Admin::showTimeStamp(),
            ])
            ->striped();
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Stack::make([
                            Split::make([
                                Admin::showQuoteProvider(),
                                Admin::showCommodity(),
                            ]),
                            Split::make([
                                Admin::showOriginPort(),
                                Admin::showDestinationPort(),
                            ]),
                            Split::make([
                                Admin::showOfferedRate(),
                                Admin::showSwitchBLFee(),
                                Admin::showAttachment(),
                            ]),
                            Admin::showValidity(),
                        ])->space(2),
                    ])->columnSpanFull(),
                ])->columnSpanFull(),
                Admin::showTimeStamp(),
            ])->contentGrid([
                'md' => 2,
                'xl' => 3,
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
