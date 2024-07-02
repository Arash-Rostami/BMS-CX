<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentRequestResource\Widgets\StatsOverview;
use App\Filament\Resources\PaymentRequestResource\Pages;
use App\Filament\Resources\PaymentRequestResource\RelationManagers;
use App\Models\PaymentRequest;
use App\Rules\EnglishAlphabet;
use App\Services\TableObserver;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Collection;


class PaymentRequestResource extends Resource

{
    protected static ?string $model = PaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Operational Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Admin::getStatus()
                            ])
                            ->hidden(fn(string $operation) => $operation === 'create')
                            ->collapsible(),
                        Section::make('Linked to CPS (Centralized Payment Service)')
                            ->icon('heroicon-o-information-circle')
                            ->description('You need to select your department to follow the appropriate organizational procedure.')
                            ->schema([
                                Admin::getDepartment(),
                                Admin::getCPSReasons(),
                                Admin::getCostCenter(),
                                Admin::getTypeOfPayment(),
                            ])
                            ->columns(4)
                            ->collapsible(),
                        Group::make()
                            ->schema([
                                Section::make('Order Details')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Admin::getOrderNumber(),
                                            Admin::getTotalOrPart(),
                                            Admin::getOrderPart(),
                                        ]),
                                        Grid::make(2)->schema([
                                            Admin::getType(),
                                            Admin::getBeneficiary(),
                                            Admin::getPurpose(),
                                        ]),
                                        Grid::make(2)->schema([]),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->hidden(fn(Get $get) => $get('department_id') != 6),
                                Section::make('Account Details')
                                    ->schema([
                                        Admin::getBankName(),
                                        Admin::getAccountNumber(),
                                        Grid::make(2)->schema([]),
                                        Admin::getSupplier(),
                                        Admin::getContractor(),
                                        Admin::getPayee(),
                                        Admin::getRecipientName(),
                                        Admin::getBeneficiaryAddress(),
                                        Admin::getBankAddress(),
                                        Admin::getDescription(),
                                        Repeater::make('attachments')
                                            ->relationship('attachments')
                                            ->label('Attachments')
                                            ->schema([
                                                Group::make()
                                                    ->schema([
                                                        Admin::getAttachmentFile()
                                                    ])->columnSpan(2),
                                                Group::make()
                                                    ->schema([
                                                        Section::make()
                                                            ->schema([
                                                                Admin::getAttachmentFileName()
                                                            ])
                                                    ])
                                                    ->columnSpan(2)
                                            ])->columns(4)
                                            ->itemLabel('Attachments:')
                                            ->addActionLabel('➕')
                                            ->deletable(fn(Model $record) => ($record->payments->isEmpty()))
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ])
                            ->columnSpan(2),
                        Group::make()
                            ->schema([
                                Section::make(new HtmlString('Payment Details  <span class="red"> *</span>'))
                                    ->schema([
                                        Admin::getCurrency(),
                                        Admin::getPayableAmount(),
                                        Admin::getTotalAmount(),
                                        Admin::getDeadline()
                                    ])->collapsible(),
                                Section::make(new HtmlString('IDs'))
                                    ->schema([
                                        Admin::getSwiftCode(),
                                        Admin::getIBANCode(),
                                        Admin::getIFSCCode(),
                                        Admin::getMICRCode()
                                    ])
                                    ->collapsible()
                            ])->columns(1),
                    ])
                    ->columnSpanFull()
                    ->columns(3)
            ]);
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
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Request Details')
                            ->schema([
                                Admin::viewReason(),
                                Admin::viewType(),
                                Admin::viewDepartment(),
                                Admin::viewOrder(),
                                Admin::viewAmount(),
                                Admin::viewDeadline(),
                                Admin::viewStatus(),
                            ])->columns(3),
                        Tabs\Tab::make('Account Details')
                            ->schema([
                                Admin::viewBeneficiaryName(),
                                Admin::viewRecipientName(),
                                Admin::viewBankName(),
                                Admin::viewAccountNumber(),
                                Admin::viewSwiftCode(),
                                Admin::viewIBAN(),
                                Admin::viewIFSC(),
                                Admin::viewMICR(),
                            ])->columns(2),
                        Tabs\Tab::make('Extra Details')
                            ->schema([
                                Admin::viewBeneficiaryAddress(),
                                Admin::viewBankAddress(),
                                Admin::viewDescription(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getWidgets(): array
    {
        return [StatsOverview::class];
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            Operational\PaymentRequestResource\RelationManagers\OrderRelationManager::class,
            Operational\PaymentRequestResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\PaymentRequestResource\Pages\ListPaymentRequests::route('/'),
            'create' => Operational\PaymentRequestResource\Pages\CreatePaymentRequest::route('/create'),
            'edit' => Operational\PaymentRequestResource\Pages\EditPaymentRequest::route('/{record}/edit'),
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
        return static::getModel()::where('status', 'new')->count();
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([AdminOrder::filterCreatedAt(), AdminOrder::filterSoftDeletes()])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
//                    ->before(function (EditAction $action, Model $record) {
////                        if ($record->payments) {
//                            Notification::make()
//                                ->warning()
//                                ->title('You cannot delete attachment while the payment has been made for it!')
//                                ->body('Choose a plan to continue.')
//                                ->persistent()
//                                ->send();
//                            $action->halt();
////                        }
//                    }),
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
                        }), RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                ])
            ])
            ->defaultSort('created_at', 'desc')
            ->poll(60)
            ->groups([
                Admin::filterByDepartment(),
                Admin::filterByOrder(),
                Admin::filterByReason(),
                Admin::filterByType(),
                Admin::filterByCurrency(),
                Admin::filterByContractor(),
                Admin::filterBySupplier(),
                Admin::filterByPayee(),
                Admin::filterByStatus(),
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
                                Admin::showDepartment(),
                                Admin::showReferenceNumber(),
                                Admin::showInvoiceNumber(),
                                Admin::showPart(),
                                Admin::showReasonForPayment(),
                                Admin::showType(),
                            ]),
                            Split::make([
                                Admin::showBeneficiaryName(),
                                Admin::showBankName(),
                                Admin::showStatus(),
                            ]),
                            Split::make([
                                Admin::showPayableAmount(),
                                Admin::showDeadline(),
                                TableObserver::showMissingData(-6)
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
                TableObserver::showMissingData(-5),
                Admin::showDepartment(),
                Admin::showStatus(),
                Admin::showReferenceNumber(),
                Admin::showInvoiceNumber(),
                Admin::showPart(),
                Admin::showReasonForPayment(),
                Admin::showType(),
                Admin::showPayableAmount(),
                Admin::showCostCenter(),
                Admin::showBeneficiaryName(),
                Admin::showBeneficiaryAddress(),
                Admin::showBankName(),
                Admin::showBankAddress(),
                Admin::showDeadline(),
                Admin::showExtraDescription(),
                Admin::showBankName(),
                Admin::showAccountNumber(),
                Admin::showSwiftCode(),
                Admin::showIBAN(),
                Admin::showIFSC(),
                Admin::showMICR(),
                Admin::showRequestMaker(),
                Admin::showStatusChanger()
            ])->striped();
    }
}
