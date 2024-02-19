<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\PaymentResource\Pages\Admin;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;

use Filament\Forms\Components\Group;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;

use Filament\Forms\Form;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                                        Admin::getOrder(),
                                        Admin::getPaymentRequest(),
                                        Admin::getAccountNumber(),
                                        Admin::getBankName(),
                                        Admin::getNotes()
                                    ])
                                    ->columns(2)
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
                                        Admin::getAttacment()
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
                    ->collapsed(fn($operation) => $operation == 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            Operational\PaymentResource\RelationManagers\OrderRequestsRelationManager::class,
            Operational\PaymentResource\RelationManagers\OrdersRelationManager::class,
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

    public static function getNavigationBadge(): ?string
    {

        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

}
