<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationSubscriptionResource\Pages;
use App\Filament\Resources\NotificationSubscriptionResource\RelationManagers;
use App\Filament\Resources\Operational\NotificationSubscriptionResource\Pages\Admin;
use App\Models\NotificationSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationSubscriptionResource extends Resource
{
    protected static ?string $model = NotificationSubscription::class;


    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Operational Data';

    protected static ?int $navigationSort = 11;


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Admin::getNotifiableModule(),
                Admin::getUser(),
                Admin::getNotifiableRecord(),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Admin::getCreate(),
                        Admin::getUpdate(),
                        Admin::getDelete(),
                    ])
                    ->columnSpan('full'),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Admin::getEmailOption(),
                        Admin::getInAppOption(),
                        Admin::getSMSOpton(),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('user');
            })
            ->columns([
                Admin::showNotifiableModule(),
                Admin::showUser(),
                Admin::showCreate(),
                Admin::showUpdate(),
                Admin::showDelete(),
                Admin::showEmailOption(),
                Admin::showInAppOption(),
                Admin::showSMSOption(),
                Admin::showTimeStamp(),
            ])
            ->filters([
                Admin::filterBasedOnModule()
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => in_array(auth()->user()->role, ['admin', 'manager']) || $record->user_id === auth()->user()->id),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => in_array(auth()->user()->role, ['admin', 'manager']) || $record->user_id === auth()->user()->id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => in_array(auth()->user()->role, ['admin', 'manager']) || auth()->user()->user_id === auth()->user()->id),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Operational\NotificationSubscriptionResource\Pages\ManageNotificationSubscriptions::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !isSimpleSidebar();
    }
}
