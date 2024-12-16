<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentResource\Widgets\StatsOverview;
use App\Models\Payment;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
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


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'reference_number';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('Payment Information')
                                    ->schema([
                                        Admin::getPaymentRequest(),
                                        Admin::getDate(),
                                        Admin::getTransactionID(),
                                        Admin::getNotes()
                                    ])->columns(2)
                                    ->collapsible()
                            ])
                            ->columnSpan(2),
                        Group::make()
                            ->schema([
                                Section::make(new HtmlString('Payment Details  <span class="red"> *</span>'))
                                    ->schema([
                                        Admin::getCurrency(),
                                        Admin::getAmount(),
                                        Admin::getPayer(),
                                    ])->collapsible()
                            ])->columns(1),
                    ])
                    ->columnSpanFull()
                    ->columns(3),

                /*Additional Attachments*/
                Repeater::make('attachments')
                    ->relationship('attachments')
                    ->label('Attachments')
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Admin::getAttachment()
                                    ])
                            ])->columnSpan(2),
                        Group::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Admin::getTitleOfAttachment()
                                    ])
                            ])->columnSpan(2)
                    ])->columns(4)
                    ->itemLabel('Attachments:')
                    ->addable(fn($operation) => ($operation === 'edit') ? auth()->user()->can('canEditInput', Payment::class) : true)
                    ->deletable(fn($operation) => ($operation === 'edit') ? auth()->user()->can('canEditInput', Payment::class) : true)
                    ->addActionLabel('➕')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Admin::viewOrder(),
                Admin::viewPaymentRequest(),
                Admin::viewPaymentRequestReason(),
                Admin::viewPaymentType(),
                Admin::viewTransferredAmount(),
                Admin::viewPaymentRequestDetail(),
                Admin::viewPayer(),
                Admin::viewTransactionID(),
                Admin::viewDate(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            Operational\PaymentResource\RelationManagers\PaymentRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\PaymentResource\Pages\ListPayments::route('/'),
            'create' => Operational\PaymentResource\Pages\CreatePayment::route('/create'),
            'edit' => Operational\PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [StatsOverview::class];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        $count = static::getModel()::query()
            ->filterByUserPaymentRequests($user)
            ->count();

        return (string)$count;
    }


    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }


    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return PaymentResource::getUrl('edit', ['record' => $record]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '💰  ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->filterByUserPaymentRequests(auth()->user()))
            ->filters([
                AdminOrder::filterCreatedAt(),
                Admin::filterDepartments(),
                Admin::filterCostCenter(),
                Admin::filterReason(),
                AdminOrder::filterSoftDeletes(),
            ])
            ->filtersFormWidth(MaxWidth::FourExtraLarge)
            ->filtersFormColumns(5)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->groupingSettingsInDropdownOnDesktop()
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(fn(Model $record) => Admin::send($record)),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-c-inbox-arrow-down')
                    ->action(function (Model $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadHtml(view('filament.pdfs.payment', ['record' => $record])
                                ->render())
                                ->stream();
                        }, 'BMS-' . $record->reference_number . '.pdf');
                    }),
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
                    PrintBulkAction::make(),
                ])
            ])
            ->paginated([10, 20, 30])
            ->defaultSort('created_at', 'desc')
            ->poll('120s')
            ->groups([
                Admin::filterByPayer(),
                Admin::filterByCurrency(),
                Admin::filterByBalance(),
                Admin::filterByPaymentRequest(),
                Admin::filterByTransferringDate(),
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
                                Admin::showPaymentRequest(),
                                Admin::showPaymentRequestType(),
                                Admin::showTimeGap(),
                            ]),
                            Split::make([
                                Admin::showTransferredAmount(),
                                Admin::showBalance(),
                            ]),
                        ])->space(2),
                    ])
                ])->columnSpanFull(),
                Admin::showTimeStamp()
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showID(),
                Admin::showPaymentRequest(),
                Admin::showPaymentRequestType(),
                Admin::showPaymentRequestID(),
                Admin::showAmount(),
                Admin::showBalance(),
                Admin::showCurrency(),
                Admin::showRequestedAmount(),
                Admin::showTotalAmount(),
                Admin::showDate(),
                Admin::showTimeGap(),
                Admin::showPayer(),
                Admin::showTransactionID(),
                Admin::showCreator(),
                TableObserver::showMissingData(-3),
                Admin::showTimeStamp()
            ])->striped();
    }
}
