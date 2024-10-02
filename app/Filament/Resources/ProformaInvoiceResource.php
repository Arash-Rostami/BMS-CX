<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\Admin;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Widgets\StatsOverview;
use App\Models\ProformaInvoice;
use App\Services\AttachmentDeletionService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintBulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;


    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Pro forma Invoices';


    protected static ?string $navigationGroup = 'Operational Data';


    protected static ?string $recordTitleAttribute = 'reference_number';


    protected static ?int $navigationSort = 2;


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
                        Section::make(new HtmlString('Notes <span class="red"> </span>'))
                            ->schema([
                                Admin::getDetails(),
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
            \App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\OrdersRelationManager::class,
            \App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\MainPaymentRequestsRelationManager::class,
            \App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\PaymentRequestsRelationManager::class,
            \App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\MainPaymentsRelationManager::class,
            \App\Filament\Resources\Operational\ProformaInvoiceResource\RelationManagers\PaymentsRelationManager::class,

        ];
    }

    public static function getWidgets(): array
    {
        return [StatsOverview::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\ProformaInvoiceResource\Pages\ListProformaInvoices::route('/'),
            'create' => Operational\ProformaInvoiceResource\Pages\CreateProformaInvoice::route('/create'),
            'edit' => Operational\ProformaInvoiceResource\Pages\EditProformaInvoice::route('/{record}/edit'),
        ];
    }


    private static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([Admin::filterProforma(), AdminOrder::filterSoftDeletes()])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateDescription('Once you create your first record, it will appear here.')
            ->searchDebounce('1000ms')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()
                    ->color('info')
                    ->modalWidth(MaxWidth::Medium)
                    ->modalIcon('heroicon-o-clipboard-document-list')
                    ->record(fn(Model $record) => $record)
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->after(fn(Model $replica) => Admin::syncProformaInvoice($replica))
                    ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.proforma-invoices.edit', ['record' => $replica->id,])),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(fn(Model $record) => Admin::send($record))
                    ->hidden(fn(?Model $record) => $record && ($record->activeApprovedPaymentRequests->isNotEmpty() || $record->activeOrders->isNotEmpty())),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-c-inbox-arrow-down')
                    ->action(function (Model $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadHtml(view('filament.pdfs.proformaInvoice', ['record' => $record])
                                ->render())
                                ->stream();
                        }, 'BMS-' . $record->reference_number . '.pdf');
                    }),

                Tables\Actions\Action::make('createPaymentRequest')
                    ->label('Smart Payment')
                    ->url(fn($record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'proforma-invoice']))
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn(Collection $records) => Admin::separateRecordsIntoDeletableAndNonDeletable($records))
                        ->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                    PrintBulkAction::make(),
                ])
            ])
            ->poll('120s')
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultSort('reference_number', 'desc')
            ->groups([
                Admin::groupProformaInvoiceRecords(),
                Admin::groupProformaDateRecords(),
                Admin::groupPartRecords(),
                Admin::groupCategoryRecords(),
                Admin::groupProductRecords(),
                Admin::groupBuyerRecords(),
                Admin::groupSupplierRecords(),
                Admin::groupContractRecords(),
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
                                Admin::showShipmentPart(),
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
                Admin::showShipmentPart(),
                Admin::showContractName(),
                Admin::showCreator(),
                Admin::showStatus(),
            ])->striped();
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
        return static::getModel()::where('status', 'pending')->count();
    }


    public static function getGloballySearchableAttributes(): array
    {
        return ['proforma_number', 'reference_number'];
    }


    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ProformaInvoiceResource::getUrl('edit', ['record' => $record]);
    }


    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '📋 ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }
}
