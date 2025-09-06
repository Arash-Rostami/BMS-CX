<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\QuoteRequestResource\Pages\Admin;
use App\Models\QuoteRequest;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 9;

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-bookmark')
            ->recordClasses(fn(Model $record) => ($record->extra['use_markdown'] ?? false) ? 'major-row' : '')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->filters([Admin::filterCreatedAt(), Admin::filterSoftDeletes()])
            ->defaultSort('created_at', 'desc')
            ->poll('900s')
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Linked to SES (Smart Emailing Service)')
                    ->icon('heroicon-o-information-circle')
                    ->description('⚠️ Once created, this quote request will automatically send (via email) a link to the questionnaire for all selected quote providers. This process cannot be UNDONE!')
                    ->schema([
                        Section::make()
                            ->schema([
                                Admin::getMarkDown(),
                                Admin::getTitle(),
                                Admin::getQuoteProviders()
                            ])->columns(3),
                        Admin::getOriginPort(),
                        Admin::getDestinationPort(),
                        Admin::getCommodity(),
                        Admin::getPackaging(),
                        Admin::getContainerType(),
                        Admin::getGrossWeight(),
                        Admin::getQuantity(),
                        Admin::getTargetRate(),
                        Admin::getSwitchBL(),
                        Admin::getTargetSwitchBL(),
                        Admin::getTargetLocalCharges(),
                        Admin::getValidity(),
                        Admin::getExtraInfo(),
                        Admin::getTemplateField(),
                        Admin::getEmailBody(),
                    ])->columns(3),

            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showTitle(),
                Admin::showResponseRate(),
                Admin::showMarkdown(),
                Admin::showOriginPort(),
                Admin::showDestinationPort(),
                Admin::showContainerType(),
                Admin::showSwitchBL(),
                Admin::showCommodity(),
                Admin::showPackaging(),
                Admin::showGrossWeight(),
                Admin::showQuantity(),
                Admin::showTargetRate(),
                Admin::showTargetLocalCharges(),
                Admin::showTargetSwitchBLFee(),
                Admin::showValidity(),
                Admin::showRequester(),
                Admin::showTimeStamp(),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'packaging',
                'product'
            ])
            ->withCount([
                'quoteTokens as token_count',
                'quotes as response_count',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showTitle(),
                        Admin::showResponseRate(),
                        Admin::showMarkdown(),
                        Admin::showOriginPort(),
                        Admin::showDestinationPort(),
                    ]),
                ])->space(3),
                Split::make([
                    Split::make([
                        Admin::showCommodity(),
                        Admin::showPackaging(),
                        Admin::showContainerType(),
                        Admin::showValidity(),
                        Admin::showRequester(),
                    ])->columnSpan(3),
                ])->collapsible(),
            ]);
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

    public static function getRelations(): array
    {
        return [
            Operational\QuoteRequestResource\RelationManagers\QuotesRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);
    }
}
