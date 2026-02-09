<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\Operational\PaymentResource\Widgets\StatsOverview;
use App\Models\Payment;
use App\Services\SmartCacheManager;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $pollingInterval = null;

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function configureCommonTableSettings(Table $table): Table
    {
        return (new ListPayments())->configureCommonTableSettings($table);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('General')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Admin::getPaymentRequest(),
                                        Admin::getNotes(),
                                    ])->columnSpan(2),
                                Group::make()
                                    ->schema([
                                        Admin::getAllowedCurrencies(),
                                        Admin::getCurrency(),
                                        Admin::getAmount(),
                                        Admin::getPayer(),
                                        Admin::getStatus(),
                                    ])->columnSpan(1),
                            ])
                            ->columns(3)
                            ->columnSpan(3),

                        Section::make('Details')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Admin::getExchangeRate(),
                                        Admin::getEuroEquivalent(),
                                        Admin::getBankChargesCurrency(),
                                        Admin::getBankCharges(),
                                    ])->columns(1),

                                Group::make()
                                    ->schema([
                                        Admin::getDate(),
                                        Admin::getTransactionID(),
                                        Admin::getBypassCashLedger(),
                                    ])->columns(1),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),

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

    public static function getClassicLayout(Table $table): Table
    {
        return (new ListPayments())->getClassicLayout($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'attachments',
                'order',
                'paymentRequests',
                'user',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '💰  ' . $record->reference_number . '  🗓️ ' . $record->created_at->format('M d, Y');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return PaymentResource::getUrl('edit', ['record' => $record]);
    }

    public static function getModernLayout(Table $table): Table
    {
        return (new ListPayments())->getModernLayout($table);

    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $filters = ['user_id' => $user->id, 'type' => 'total_count'];

        $count = SmartCacheManager::remember('Payment', $filters, 15, function () use ($user) {
            return static::getModel()::query()
                ->filterByUserPaymentRequests($user)
                ->count();
        });

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\PaymentResource\Pages\ListPayments::route('/'),
            'create' => Operational\PaymentResource\Pages\CreatePayment::route('/create'),
            'edit' => Operational\PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            Operational\PaymentResource\RelationManagers\PaymentRequestsRelationManager::class,
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
                Admin::viewOrder(),
                Admin::viewPaymentRequest(),
                Admin::viewPaymentRequester(),
                Admin::viewDepartment(),
                Admin::viewCostCenter(),
                Admin::viewPaymentRequestReason(),
                Admin::viewPaymentType(),
                Admin::viewTransferredAmount(),
                Admin::viewPaymentRequestDetail(),
                Admin::viewPayer(),
                Admin::viewTransactionID(),
                Admin::viewStatus(),
                Admin::viewDate(),
                Admin::viewExchangeRate(),
                Admin::viewEuroEquivalent(),
                Admin::viewBankCharges(),
            ])->columns(3);
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
