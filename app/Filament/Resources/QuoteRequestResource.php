<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\QuoteRequestResource\Pages\Admin;
use App\Filament\Resources\QuoteRequestResource\Pages;
use App\Filament\Resources\QuoteRequestResource\RelationManagers;
use App\Models\QuoteRequest;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Columns\Layout\Stack;


class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Operational Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Linked to SES (Smart Emailing Service)')
                    ->icon('heroicon-o-information-circle')
                    ->description('Once you select and create this quote request, it will be automatically sent as a questionnaire via email to designated quote providers. This process cannot be undone.')
                    ->schema([
                        Section::make()
                            ->schema([
                                Admin::getQuoteProviders()
                            ]),
                        Admin::getOriginPort(),
                        Admin::getDestinationPort(),
                        Admin::getPackaging(),
                        Admin::getContainerType(),
                        Admin::getCommodity(),
                        Admin::getGrossWeight(),
                        Admin::getQuantity(),
                        Admin::getTargetRate(),
                        Admin::getTargetTHC(),
                        Admin::getTargetLocalCharges(),
                        Admin::getTargetSwitchBL(),
                        Admin::getValidity(),
                        Admin::getSwitchBL(),
                        Admin::getExtraInfo(),
                    ])->columns(3),
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
            ->filters([Admin::filterCreatedAt(), Admin::filterSoftDeletes()])
            ->poll(60)
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
                Admin::showResponseRate(),
                Admin::showOriginPort(),
                Admin::showDestinatonPort(),
                Admin::showContainerType(),
                Admin::showSwitchBL(),
                Admin::showCommodity(),
                Admin::showPackaging(),
                Admin::showGrossWeight(),
                Admin::showQuantity(),
                Admin::showTargetRate(),
                Admin::showTargetTHC(),
                Admin::showTargetLocalCharges(),
                Admin::showTargetSwitchBLFee(),
                Admin::showValidity(),
                Admin::showRequester(),
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
                                Admin::showResponseRate(),
                                Admin::showOriginPort(),
                                Admin::showDestinatonPort(),
                            ]),
                            Split::make([
                                Admin::showContainerType(),
                                Admin::showCommodity(),
                                Admin::showPackaging(),
                            ]),
//                            Split::make([
                            Admin::showValidity(),
//                            ]),
                        ])->space(2),
                    ])
                ])->columnSpanFull(),
                Admin::showRequester(),
                Admin::showTimeStamp(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            Operational\QuoteRequestResource\RelationManagers\QuotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\QuoteRequestResource\Pages\ListQuoteRequests::route('/'),
            'create' => Operational\QuoteRequestResource\Pages\CreateQuoteRequest::route('/create'),
            'view' => Operational\QuoteRequestResource\Pages\ViewQuoteRequest::route('/{record}'),
            'edit' => Operational\QuoteRequestResource\Pages\EditQuoteRequest::route('/{record}/edit'),
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
