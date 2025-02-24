<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\Operational\PaymentResource\Widgets\StatsOverview;
use App\Models\Payment;
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
use Illuminate\Support\HtmlString;


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
        return $table;
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
                Admin::viewPaymentRequester(),
                Admin::viewDepartment(),
                Admin::viewCostCenter(),
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

    public static function shouldRegisterNavigation(): bool
    {
        return !isSimpleSidebar();
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
        return (new ListPayments())->configureCommonTableSettings($table);
    }


    public static function getModernLayout(Table $table): Table
    {
        return (new ListPayments())->getModernLayout($table);

    }

    public static function getClassicLayout(Table $table): Table
    {
        return (new ListPayments())->getClassicLayout($table);
    }
}
