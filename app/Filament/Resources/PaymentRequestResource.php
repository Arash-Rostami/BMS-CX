<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\ListPaymentRequests;
use App\Filament\Resources\Operational\PaymentRequestResource\Widgets\StatsOverview;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Services\AttachmentDeletionService;
use App\Services\SmartCacheManager;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;


class PaymentRequestResource extends Resource

{
    use InteractsWithActions;

    protected static ?string $model = PaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?array $badgeData = null;

    protected static ?int $navigationSort = 4;

    protected static ?string $pollingInterval = null;

    protected static ?string $recordTitleAttribute = 'reference_number';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected $listeners = ['fillFormData'];

    public static function getNavigationGroup(): ?string
    {
        return config('nav.first_category');
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return (new ListPaymentRequests())->configureCommonTableSettings($table);
    }

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
                        // Status
                        Section::make('💫 Status')
                            ->schema([Admin::getStatus()])
                            ->hidden(fn(string $operation) => $operation === 'create')
                            ->collapsible(),
                        // Chat
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
                        //Create for all departments, excluding CX
                        Wizard::make([
                            Wizard\Step::make('1')
                                ->icon('heroicon-o-building-storefront')
                                ->completedIcon('heroicon-m-check-circle')
                                ->description('Organizational details')
                                ->schema([
                                    Section::make(new HtmlString('Linked to CPS (Centralized Payment Service)'))
                                        ->icon('heroicon-o-information-circle')
                                        ->description(' You need to select your department (then currency) to follow the appropriate organizational procedure.')
                                        ->schema([
                                            Admin::getDepartment(),
                                            Admin::getCurrency(),
                                            Admin::getCPSReasons(),
                                            Admin::getCostCenter(),
                                            Admin::getTypeOfPayment()
                                        ])->columns(5)
                                        ->collapsible(),
                                ]),
                            Wizard\Step::make('2')
                                ->icon('heroicon-s-globe-alt')
                                ->completedIcon('heroicon-m-check-circle')
                                ->description('Transfer details')
                                ->schema([
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
                                                    Admin::getSupplier(),
                                                    Admin::getContractor(),
                                                    Admin::getPayee(),
                                                    Admin::getRecipientName(),
                                                    Admin::getPaymentMethod(),
                                                    Admin::getBankName(),
                                                    Admin::getAccountNumber(),
                                                    Admin::getCardTransfer(),
                                                    Admin::getShebaAccount(),
                                                    Grid::make(2)->schema([]),
                                                    Admin::getBankAddress(),
                                                    Admin::getBeneficiaryAddress(),
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
                                                                    return new HtmlString('<span>Of course, it is an empty attachment.</span>');
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
                                ]),
                            Wizard\Step::make('3')
                                ->icon('heroicon-o-credit-card')
                                ->completedIcon('heroicon-m-check-circle')
                                ->description('Payment details')
                                ->schema([
                                    Group::make()
                                        ->schema([
                                            Section::make(new HtmlString('Payment Details  <span class="red"> *</span>'))
                                                ->schema([
                                                    Admin::getPayableAmount(),
                                                    Admin::getAdjustmentAmount(),
                                                    Admin::getTotalAmount(),
                                                    Admin::getDeadline(),
                                                    Admin::getCaseNumber(),
                                                ])
                                                ->columnSpan(1)
                                                ->collapsible(),
                                            Section::make(new HtmlString('International Account Details'))
                                                ->schema([
                                                    Admin::getSwiftCode(),
                                                    Admin::getIBANCode(),
                                                    Admin::getIFSCCode(),
                                                    Admin::getMICRCode()
                                                ])
                                                ->visible(fn(Get $get) => $get('currency') != 'Rial')
                                                ->columnSpan(1)
                                                ->collapsible()
                                        ])
                                        ->columns(2),
                                ]),
                        ])
                            ->hidden(fn(string $operation, Get $get) => $operation !== 'create' or $get('department_id') == 6)
                            ->nextAction(fn(Action $action) => $action->label('⮞')->tooltip('⏩ Next step')->extraAttributes(['id' => 'next-step-button']))
                            ->previousAction(fn(Action $action) => $action->label('⮜')->tooltip('⏪ Previous step')),
                        //Edit for all departments and Create for CX
                        Group::make()
                            ->schema([
                                Section::make(new HtmlString('Organizational Details'))
                                    ->schema([
                                        Admin::getDepartment(),
                                        Admin::getCurrency(),
                                        Admin::getCPSReasons(),
                                        Admin::getCostCenter(),
                                        Admin::getTypeOfPayment()
                                    ])->columns(5)
                                    ->collapsible(),
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
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->hidden(fn(string $operation, Get $get) => $get('department_id') != 6),
                                Group::make()->schema([
                                    Section::make('Account Details')
                                        ->schema([
                                            Group::make()->schema([
                                                Admin::getSupplier(),
                                                Admin::getContractor(),
                                                Admin::getPayee(),
                                                Admin::getRecipientName(),
                                                Admin::getPaymentMethod(),
                                                Admin::getBankName(),
                                                Admin::getAccountNumber(),
                                                Admin::getCardTransfer(),
                                                Admin::getShebaAccount(),
                                                Admin::getBankAddress(),
                                                Admin::getBeneficiaryAddress(),
                                                Admin::getDescription(),
                                                Admin::getAttachmentToggle(),
                                                Section::make()->schema([
                                                    Admin::getSourceSelection(),
                                                    Admin::getAllProformaInvoicesOrOrders(),
                                                    Admin::getProformaInvoicesAttachments(),
                                                ])
                                                    ->columns(3)
                                                    ->visible(fn($get) => $get('use_existing_attachments')),
                                            ])->columns(2),
                                            Repeater::make('attachments')
                                                ->relationship('attachments')
                                                ->label('Attachments')
                                                ->schema([
                                                    Group::make()->schema([
                                                        Hidden::make('id'),
                                                        Admin::getAttachmentFile(),
                                                    ])->columnSpan(2),

                                                    Group::make()->schema([
                                                        Section::make()->schema([
                                                            Admin::getAttachmentFileName(),
                                                        ]),
                                                    ])->columnSpan(2),
                                                ])
                                                ->columns(4)
                                                ->itemLabel('Attachments:')
                                                ->addActionLabel('➕')
                                                ->extraItemActions([
                                                    Action::make('deleteAttachment')
                                                        ->label('deleteMe')
                                                        ->visible(fn($operation, $record) => $operation == 'edit' || ($record === null) || ($record->payments->isEmpty()))
                                                        ->icon('heroicon-o-trash')
                                                        ->color('danger')
                                                        ->modalHeading('Delete Attachment?')
                                                        ->action(fn(array $arguments, Repeater $component) => AttachmentDeletionService::removeAttachment($component, $arguments['item'])
                                                        )
                                                        ->modalContent(function (Action $action, array $arguments, Repeater $component, $operation, ?Model $record) {
                                                            if (str_contains($arguments['item'], 'record')) {
                                                                return AttachmentDeletionService::validateAttachmentExists($component, $arguments['item'], $operation, $action, $record);
                                                            }
                                                            return new HtmlString('<span>Of course, it is an empty attachment.</span>');
                                                        })
                                                        ->modalSubmitActionLabel('Confirm')
                                                        ->modalWidth(MaxWidth::Medium)
                                                        ->modalIcon('heroicon-s-exclamation-triangle'),
                                                ])
                                                ->deletable(false)
                                                ->columnSpanFull()
                                                ->collapsible()
                                                ->collapsed(),
                                        ])
                                        ->columnSpan(4)
                                        ->collapsible(),
                                    Section::make(new HtmlString('Payment Details  <span class="red"> *</span>'))
                                        ->schema([
                                            Admin::getPayableAmount(),
                                            Admin::getAdjustmentAmount(),
                                            Admin::getTotalAmount(),
                                            Admin::getDeadline(),
                                            Admin::getCaseNumber(),
                                            Admin::getSwiftCode(),
                                            Admin::getIBANCode(),
                                            Admin::getIFSCCode(),
                                            Admin::getMICRCode(),
                                        ])
                                        ->columnSpan(2)
                                        ->collapsible(),
                                    Admin::getCreditAdjustmentSection()
                                        ->columnSpan(2),
                                ])->columns(6)
                            ])
                            ->hidden(fn(string $operation, Get $get) => ($operation === 'create' && $get('department_id') != 6))
                    ])
                    ->columnSpanFull()
                    ->columns(1)
            ]);
    }

    public static function getClassicLayout(Table $table)
    {
        return (new ListPaymentRequests())->getClassicLayout($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'attachments',
                'payments',
                'contractor',
                'costCenter',
                'department',
                'order',
                'proformaInvoice',
                'beneficiary',
                'reason',
                'supplier',
                'user'
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '💳 ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return PaymentRequestResource::getUrl('edit', ['record' => $record]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return (new ListPaymentRequests())->getModernLayout($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $newCount = self::getNewRequestsCount();

        if ($newCount > 0) {
            return "{$newCount} New";
        }

        $user = auth()->user();
        $filters = ['user_id' => $user->id, 'type' => 'total_count'];

        $totalCount = SmartCacheManager::remember('PaymentRequest', $filters, 15, function () use ($user) {
            return static::getModel()::query()
                ->authorizedForUser($user)
                ->count();
        });

        return (string)$totalCount;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return self::getNewRequestsCount() > 0 ? 'danger' : 'primary';
    }

    public static function getNewRequestsCount(): int
    {
        $user = auth()->user();
        $filters = ['user_id' => $user->id, 'type' => 'pending_count'];

        return SmartCacheManager::remember('PaymentRequest', $filters, 15, function () use ($user) {
            return static::getModel()::query()
                ->authorizedForUser($user)
                ->where('status', 'pending')
                ->count();
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\PaymentRequestResource\Pages\ListPaymentRequests::route('/'),
            'create' => Operational\PaymentRequestResource\Pages\CreatePaymentRequest::route('/create'),
            'edit' => Operational\PaymentRequestResource\Pages\EditPaymentRequest::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            Operational\PaymentRequestResource\RelationManagers\ProformaInvoiceRelationManager::class,
            Operational\PaymentRequestResource\RelationManagers\OrderRelationManager::class,
            Operational\PaymentRequestResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [StatsOverview::class];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->columnSpanFull()
                            ->schema([
                                Admin::viewReason(),
                                Admin::viewType(),
                                Admin::viewRequester(),
                                Admin::viewDepartment(),
                                Admin::viewCostCenter(),
                                Admin::viewStatus(),
                                Admin::viewBeneficiaryName(),
                                Admin::viewRecipientName(),
                                Admin::viewAmount(),
                                Admin::viewTotalAmount(),
                                Admin::viewDeadline(),
                                Admin::viewBankName(),
                                Admin::viewAccountNumber(),
                                Admin::viewBankAddress(),
                                Admin::viewBeneficiaryAddress(),
                                Admin::viewDescription()
                            ])->columns(3),

                        Tabs\Tab::make('Details')
                            ->icon('heroicon-m-information-circle')
                            ->columnSpanFull()
                            ->schema([
                                Admin::viewProformaNumber(),
                                Admin::viewContractNumber(),
                                Admin::viewPart(),
                                Admin::viewBlNumber(),
                                Admin::viewBookingNumber(),
                                Admin::viewOrder(),
                                Admin::viewSwiftCode(),
                                Admin::viewIBAN(),
                                Admin::viewIFSC(),
                                Admin::viewMICR(),
                                Admin::viewCreationTime(),
                                Admin::viewStatusChanger(),
                            ])->columns(3),
                    ])
                    ->activeTab(1)
                    ->contained(false),
            ])
            ->columns(1);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !isSimpleSidebar();
    }

    public static function table(Table $table): Table
    {
        return $table;
    }
}
