<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\Admin;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\ListProformaInvoices;
use App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\MainPaymentRequestsRelationManager;
use App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\MainPaymentsRelationManager;
use App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\PaymentRequestsRelationManager;
use App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Widgets\StatsOverview;
use App\Models\ProformaInvoice;
use App\Services\AttachmentDeletionService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;


    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Pro forma Invoices';


    protected static ?string $recordTitleAttribute = 'reference_number';


    protected static ?int $navigationSort = 2;

    protected static ?int $newRequestsCount = null;


    protected static ?string $pollingInterval = null;

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
                                Admin::getCategory(),
                                Admin::getProduct(),
                                Admin::getGrade(),
                                Admin::getContract(),
                            ])
                            ->columns(3)
                            ->collapsible(),
                        Section::make('Details')
                            ->schema([
                                Admin::getPercentage(),
                                Admin::getPrice(),
                                Admin::getQuantity(),
                                Admin::getShipmentPart(),
                                Admin::getPorts(),
                                Group::make()
                                    ->schema([
                                        Admin::getAttachmentToggle(),
                                        Section::make()
                                            ->schema([
                                                Admin::getAllProformaInvoices(),
                                                Admin::getProformaInvoicesAttachments(),
                                            ])
                                            ->columns(4)
                                            ->visible(fn($get) => $get('use_existing_attachments')),
                                    ])->columnSpanFull(),
                                /*Additional Attachments*/
                                Repeater::make('attachments')
                                    ->relationship('attachments')
                                    ->label('Attachments')
                                    ->schema([
                                        Section::make()->schema([
                                            Hidden::make('id'),
                                            Admin::getFileUpload(),
                                            Admin::getAttachmentTitle()
                                        ])
                                            ->columns(2),
                                    ])->columns(4)
                                    ->itemLabel('Attachments:')
                                    ->addActionLabel('➕')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->extraItemActions([
                                        Action::make('deleteAttachment')
                                            ->label('deleteMe')
                                            ->visible(fn($operation) => $operation == 'edit')
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
                                    ->visible(fn($get) => !$get('use_existing_attachments'))
                                    ->collapsed(),
                            ])->columns(4),
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
                        Section::make(new HtmlString('Extra <span class="red"> </span>'))
                            ->schema([
                                Admin::getAssignee(),
                                Admin::getDetails(),
                            ])
                            ->collapsible(),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return (new ListProformaInvoices())->getClassicLayout($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'category',
                'product',
                'grade',
                'buyer',
                'supplier',
                'user',
                'attachments'
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '📋 ' . $record->reference_number . '  🔎 ' . $record->contract_number . ' - ' . $record->proforma_number;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ProformaInvoiceResource::getUrl('edit', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['proforma_number', 'reference_number', 'contract_number', 'category.name',
            'product.name', 'supplier.name', 'user.first_name', 'user.last_name'];
    }

    public static function getModernLayout(Table $table): Table
    {
        return (new ListProformaInvoices())->getModernLayout($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $newCount = self::getNewRequests();

        if ($newCount > 0) {
            return "{$newCount} New";
        }

        $cacheKey = 'total_count_' . str('ProformaInvoice')->slug();

        return Cache::remember($cacheKey, now()->addMinutes(15), function () {
            return static::getModel()::count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return self::getNewRequests() > 0 ? 'danger' : 'primary';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('nav.first_category');
    }

    public static function getNavigationLabel(): string
    {
        return isSimpleSidebar() ? 'Contracts' : 'Pro forma Invoices';
    }

    public static function getNewRequests(): int
    {
        if (static::$newRequestsCount !== null) {
            return static::$newRequestsCount;
        }

        $cacheKey = 'pending_count_' . str('ProformaInvoice')->slug();

        return static::$newRequestsCount = Cache::remember($cacheKey, now()->addMinutes(15), function () {
            return static::getModel()::where('status', 'pending')->count();
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\ProformaInvoiceResource\Pages\ListProformaInvoices::route('/'),
            'create' => Operational\ProformaInvoiceResource\Pages\CreateProformaInvoice::route('/create'),
            'edit' => Operational\ProformaInvoiceResource\Pages\EditProformaInvoice::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            MainPaymentRequestsRelationManager::class,
            PaymentRequestsRelationManager::class,
            MainPaymentsRelationManager::class,
            PaymentsRelationManager::class,

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
                Admin::viewContractNumber(),
                Admin::viewReferenceNumber(),
                Admin::viewProformaInvoice(),
                Admin::viewProformaDate(),
                Admin::viewCategory(),
                Admin::viewProduct(),
                Admin::viewGrade(),
                Admin::viewBuyer(),
                Admin::viewSupplier(),
                Admin::viewPercentage(),
                Admin::viewQuantity(),
                Admin::viewPrice(),
                Admin::viewShipmentPart(),
                Admin::viewTotal(),
                Admin::viewStatus()
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    private static function configureCommonTableSettings(Table $table): Table
    {
        return (new ListProformaInvoices())->configureCommonTableSettings($table);
    }
}
