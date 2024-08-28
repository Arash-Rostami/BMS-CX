<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\OrderRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\OrderRequestResource\Widgets\StatsOverview;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\OrderRequestResource\Pages;
use App\Filament\Resources\OrderRequestResource\RelationManagers;
use App\Models\OrderRequest;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Collection;


class OrderRequestResource extends Resource
{
    protected static ?string $model = OrderRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Pro-forma invoice';

    protected static ?string $slug = 'profroma-invoices';

    protected static ?string $navigationGroup = 'Operational Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Contract')
                            ->schema([
                                Admin::getProformaNumber(),
                                Admin::getProformaDate(),
                                Admin::getGrade(),
                                Admin::getPercentage(),
                                Admin::getPrice(),
                                Admin::getQuantity(),
                            ])
                            ->columns(3)
                            ->collapsible(),
                        Section::make('Details')
                            ->schema([
                                Admin::getCategory(),
                                Admin::getProduct(),
                                /*Additional Attachments*/
                                Repeater::make('attachments')
                                    ->relationship('attachments')
                                    ->label('Attachments')
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                Section::make()
                                                    ->schema([
                                                        Admin::getFileUpload()
                                                    ])
                                            ])->columnSpan(2),
                                        Group::make()
                                            ->schema([
                                                Section::make()
                                                    ->schema([
                                                        Admin::getAttachmentTitle(),
                                                    ])
                                            ])->columnSpan(2)
                                    ])->columns(4)
                                    ->itemLabel('Attachments:')
                                    ->addActionLabel('➕')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->collapsed(),
                            ])->columns(2),
                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Parties')
                            ->schema([
                                Admin::getBuyer(),
                                Admin::getSupplier(),
                            ])->collapsible(),
                        Section::make(new HtmlString('Status <span class="red"> </span>'))
                            ->schema([
                                Admin::getStatus(),
                            ])
                            ->collapsible()
                            ->collapsed(),
                        Section::make(new HtmlString('Notes <span class="red"> </span>'))
                            ->schema([
                                Admin::getDetails()
                                ])
                            ->collapsible(),
                    ])->columnSpan(1),


            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Admin::viewProformaInvoice(),
                Admin::viewProformaDate(),
                Admin::viewCategory(),
                Admin::viewProduct(),
                Admin::viewGrade(),
                Admin::viewStatus(),
                Admin::viewBuyer(),
                Admin::viewSupplier(),
                Admin::viewPercentage(),
                Admin::viewQuantity(),
                Admin::viewPrice(),
                Admin::viewTotal()
            ])->columns(3);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
    {
        return [StatsOverview::class];
    }


    public static function getRelations(): array
    {
        return [
            Operational\OrderRequestResource\RelationManagers\OrdersRelationManager::class,
            Operational\OrderRequestResource\RelationManagers\PaymentrequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\OrderRequestResource\Pages\ListOrderRequests::route('/'),
            'create' => Operational\OrderRequestResource\Pages\CreateOrderRequest::route('/create'),
            'edit' => Operational\OrderRequestResource\Pages\EditOrderRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $new = self::getNewRequests();

        if ($new > 0) return "{$new} New";

        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return self::getNewRequests() > 0 ? 'danger' : 'primary';
    }

    /**
     * @return mixed
     */
    public static function getNewRequests()
    {
        return static::getModel()::where('request_status', 'pending')->count();
    }

    private static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([AdminOrder::filterProforma(), AdminOrder::filterSoftDeletes()])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(fn(Model $record) => Admin::send($record)),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $selectedRecords) {
                            $selectedRecords->each->delete();
                            $selectedRecords->each(
                                fn(Model $selectedRecord) => Admin::send($selectedRecord)
                            );
                        }),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                ])
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('120s')
            ->groupingSettingsInDropdownOnDesktop()
            ->groups([
                Admin::groupProformaInvoiceRecords(),
                Admin::groupCategoryRecords(),
                Admin::groupProductRecords(),
                Admin::groupBuyerRecords(),
                Admin::groupSupplierRecords(),
                Admin::groupStatusRecords(),
            ]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Stack::make([
                            Split::make([
                                Admin::showCategory(),
                                Admin::showProduct(),
                                Admin::showBuyer(),
                            ]),
                            Split::make([
                                Admin::showProformaNumber(),
                                Admin::showProformaDate(),
                                Admin::showStatus(),
                            ]),
                            Split::make([
                                Admin::showGrade(),
                                Admin::showTotal(),
                            ]),
                        ])->space(2)

                    ])->columnSpanFull(true),
                ]),
                Admin::showTimeStamp()
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showID(),
                Admin::showProformaNumber(),
                Admin::showProformaDate(),
                Admin::showBuyer(),
                Admin::showSupplier(),
                Admin::showCategory(),
                Admin::showProduct(),
                Admin::showGrade(),
                Admin::showPrice(),
                Admin::showQuantity(),
                Admin::showPercentage(),
                Admin::showTotal(),
                Admin::showStatus(),
            ])->striped();
    }

}
