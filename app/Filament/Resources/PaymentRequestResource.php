<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentRequestResource\Widgets\StatsOverview;
use App\Filament\Resources\PaymentRequestResource\Pages;
use App\Filament\Resources\PaymentRequestResource\RelationManagers;
use App\Models\Attachment;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Rules\EnglishAlphabet;
use App\Services\AttachmentDeletionService;
use App\Services\TableObserver;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
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
use Livewire\Component as Livewire;


class PaymentRequestResource extends Resource

{
    protected static ?string $model = PaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Operational Data';


    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;


    protected $listeners = ['fillFormData'];

    public function fillFormData(Form $form, $proformaInvoiceId)
    {
        $proformaInvoice = ProformaInvoice::find($proformaInvoiceId);

        $form->fill([
            'requested_amount' => $proformaInvoice->price,
        ]);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([Admin::getStatus()])
                            ->hidden(fn(string $operation) => $operation === 'create')
                            ->collapsible(),
                        Section::make('')
                            ->description('📩 This section is only visible when the status is denied. Here you can add messages related to this status to facilitate discussion or clarification.')
                            ->schema([
                                Repeater::make('Chats')
                                    ->relationship('chats')
                                    ->schema([
                                        Admin::getChatContent(),
                                        Admin::getChatMentionedUsers(),
                                        Admin::getChatRecord(),
                                        Admin::getChatModule(),
                                    ])
                                    ->visible(fn(Get $get) => $get('status') == 'rejected')
                                    ->columns(4)
                                    ->deletable(true)
                                    ->live()
                                    ->addActionLabel('✏️')
                            ])
                            ->visible(fn(Get $get) => $get('status') == 'rejected')
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
                                Section::make('Pro forma Invoice/Order Details')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Admin::hiddenInvoiceNumber(),
                                            Admin::getProformaInvoiceNumber(),
                                            Admin::getProformaInvoiceNumbers(),
                                            Admin::getTotalOrPart(),
                                            Admin::getPart(),
                                            Admin::getOrder(),
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
                                        Group::make()
                                            ->schema([
                                                Admin::getAttachmentToggle(),
                                                Section::make()
                                                    ->schema([
                                                        Admin::getSourceSelection(),
                                                        Admin::getAllProformaInvoicesOrOrders(),
                                                        Admin::getProformaInvoicesAttachments(),
                                                    ])
                                                    ->columns(3)
                                                    ->visible(fn($get) => $get('use_existing_attachments')),
                                            ])->columnSpanFull(),
                                        Repeater::make('attachments')
                                            ->relationship('attachments')
                                            ->label('Attachments')
                                            ->schema([
                                                Group::make()
                                                    ->schema([
                                                        Hidden::make('id'),
                                                        Admin::getAttachmentFile()
                                                    ])->columnSpan(2),
                                                Group::make()
                                                    ->schema([
                                                        Section::make()
                                                            ->schema([
                                                                Admin::getAttachmentFileName()
                                                            ])
                                                    ])->columnSpan(2)
                                            ])->columns(4)
                                            ->itemLabel('Attachments:')
                                            ->addActionLabel('➕')
                                            ->extraItemActions([
                                                Action::make('deleteAttachment')
                                                    ->label('deleteMe')
                                                    ->visible(fn($operation, $record) => $operation == 'edit' || ($record === null) || ($record->payments->isEmpty()))
                                                    ->icon('heroicon-o-trash')
                                                    ->color('danger')
                                                    ->modalHeading('Delete Attachment?')
                                                    ->action(fn(array $arguments, Repeater $component) => AttachmentDeletionService::removeAttachment($component, $arguments['item']))
                                                    ->modalContent(function (Action $action, array $arguments, Repeater $component, $operation, ?Model $record) {
                                                        if (str_contains($arguments['item'], 'record')) {
                                                            return AttachmentDeletionService::validateAttachmentExists($component, $arguments['item'], $operation, $action, $record);
                                                        }
                                                        return new HtmlString("<span>Of course, it is an empty attachment.</span>");
                                                    })
                                                    ->modalSubmitActionLabel('Confirm')
                                                    ->modalWidth(MaxWidth::Medium)
                                                    ->modalIcon('heroicon-s-exclamation-triangle')
                                            ])
                                            ->deletable(false)
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
            Operational\PaymentRequestResource\RelationManagers\ProformaInvoiceRelationManager::class,
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


    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return PaymentRequestResource::getUrl('edit', ['record' => $record]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '💳 ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->defaultGroup('department_id')
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->filters([AdminOrder::filterCreatedAt(), AdminOrder::filterSoftDeletes()])
            ->recordClasses(fn(Model $record) => (!isset($record->order_id) || $record->department_id != 6) ? 'major-row' : '')
            ->searchDebounce('1000ms')
            ->groupingSettingsInDropdownOnDesktop()
            ->paginated([10, 15, 20])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()
                    ->visible(fn(Model $record) => $record->order_id != null || $record->proforma_invoice_number == null)
                    ->color('info')
                    ->modalWidth(MaxWidth::Medium)
                    ->modalIcon('heroicon-o-clipboard-document-list')
                    ->record(fn(Model $record) => $record)
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->beforeReplicaSaved(fn(Model $replica) => $replica->status = 'pending')
                    ->after(fn(Model $replica) => Admin::syncPaymentRequest($replica))
                    ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.payment-requests.edit', ['record' => $replica->id,])),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(?Model $record) => $record ? $record->payments->isNotEmpty() : false)
                    ->successNotification(fn(Model $record) => Admin::send($record)),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-c-inbox-arrow-down')
                    ->action(function (Model $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadHtml(view('filament.pdfs.paymentRequest', ['record' => $record])
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
                        }), RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                    PrintBulkAction::make(),
                ])
            ])
//            ->defaultSort('created_at', 'desc')
            ->poll('120s')
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
                Admin::showID(),
                Admin::showDepartment(),
                Admin::showStatus(),
                Admin::showProformaInvoiceNumber(),
                Admin::showInvoiceNumber(),
                Admin::showReferenceNumber(),
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
                Admin::showStatusChanger(),
                TableObserver::showMissingData(-5),
            ])->striped();
    }
}
