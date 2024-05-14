<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentResource\Widgets\StatsOverview;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Attachment;
use App\Models\Payment;
use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Models\PaymentRequest;
use App\Services\TableObserver;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Collection;


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Operational Data';


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
                                        Admin::getAccountNumber(),
                                        Admin::getBankName(),
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
                    ->addActionLabel('➕')
                    ->columnSpanFull()
                    ->collapsible()
//                    ->deleteAction(
//                        function (Action $action, $state, Repeater $component) {
//                            $itemData = $state;
//                            dd($itemData);
////                            $attachment = Attachment::where();
////                            if ($attachment) {
////                                File::delete($attachment->file_path);
////                                $attachment->delete();
////                            }
//                        }
//                    )
                    ->collapsed(fn($operation) => $operation == 'edit'),
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
                Admin::viewPaymentType(),
                Admin::viewTransferredAmount(),
                Admin::viewPaymentRequestDetail(),
                Admin::viewPayer(),
                Admin::viewAccountNumber(),
                Admin::viewBankName(),
                RepeatableEntry::make('attachments')
                    ->label('')
                    ->schema([
                        Admin::viewAttachments()
                    ])
                    ->columnSpanFull()
                    ->hidden(fn($state) => !$state)
            ]);
    }

    public static function getRelations(): array
    {
        return [
            Operational\PaymentResource\RelationManagers\OrderRelationManager::class,
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

        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([AdminOrder::filterCreatedAt(), AdminOrder::filterSoftDeletes()])
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
            ->poll(60)
            ->groups([
                Admin::filterByPayer(),
                Admin::filterByCurrency(),
                Admin::filterByBalance(),
                Admin::filterByPaymentRequest(),
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
                TableObserver::showMissingData(-3),
                Admin::showPaymentRequest(),
                Admin::showPaymentRequestType(),
                Admin::showAmount(),
                Admin::showBalance(),
                Admin::showRequestedAmount(),
                Admin::showRemainingAmount(),
                Admin::showTimeGap(),
                Admin::showPayer(),
                Admin::showBankName(),
                Admin::showAccountNumber(),
                Admin::showTimeStamp()
            ])->striped();
    }
}
