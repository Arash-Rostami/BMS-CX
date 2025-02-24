<?php

namespace App\Filament\Resources\Operational\NotificationSubscriptionResource\Pages;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class Admin
{


    /**
     * @return Select
     */
    public static function getNotifiableModule(): Select
    {
        return Select::make('notifiable_type')
            ->label('Module')
            ->options([
                'App\Models\ProformaInvoice' => 'Proforma Invoice',
                'App\Models\Order' => 'Order',
                'App\Models\PaymentRequest' => 'Payment Request',
                'App\Models\Payment' => 'Payment',
            ])
            ->reactive()
            ->required()
            ->columnSpan(1);
    }


    public static function getUser()
    {
        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->role, ['admin', 'manager']);

        $options = $isAdminOrManager
            ? User::query()
                ->select(['id', 'first_name', 'middle_name', 'last_name'])
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn($user) => [$user->id => $user->full_name])
            : collect([$currentUser->id => $currentUser->full_name]);

        if (!$isAdminOrManager) {
            return Hidden::make('user_id')->default($currentUser->id);
        }

        return Select::make('user_id')
            ->label('User')
            ->options($options)
            ->searchable()
            ->default(null)
            ->columnSpan(1)
            ->required();
    }


    /**
     * @return Hidden
     */
    public static function getNotifiableRecord(): Hidden
    {
        return Hidden::make('notifiable_id')
            ->default(0);
    }

    /**
     * @return Toggle
     */
    public static function getCreate(): Toggle
    {
        return Toggle::make('notify_create')
            ->label('Notify on Create')
            ->default(false);
    }

    /**
     * @return Toggle
     */
    public static function getUpdate(): Toggle
    {
        return Toggle::make('notify_update')
            ->label('Notify on Update')
            ->default(false);
    }

    /**
     * @return Toggle
     */
    public static function getDelete(): Toggle
    {
        return Toggle::make('notify_delete')
            ->label('Notify on Delete')
            ->default(false);
    }

    /**
     * @return Toggle
     */
    public static function getEmailOption(): Toggle
    {
        return Toggle::make('email')
            ->label('Email');
    }

    /**
     * @return Toggle
     */
    public static function getInAppOption(): Toggle
    {
        return Toggle::make('in_app')
            ->label('In-App');
    }

    /**
     * @return Toggle
     */
    public static function getSMSOpton(): Toggle
    {
        return Toggle::make('sms')
            ->label('SMS');
    }

    /**
     * @return TextColumn
     */
    public static function showNotifiableModule(): TextColumn
    {
        return TextColumn::make('notifiable_type')
            ->label('Module')
            ->searchable()
            ->badge()
            ->sortable()
            ->tooltip(fn($record) => $record->notifiable_id == 0
                ? 'Module-Level Subscription'
                : "Record ID: {$record->notifiable_id}")
            ->formatStateUsing(function ($state) {
                $modules = [
                    'App\Models\ProformaInvoice' => 'Proforma Invoice',
                    'App\Models\Order' => 'Order',
                    'App\Models\PaymentRequest' => 'Payment Request',
                    'App\Models\Payment' => 'Payment',
                ];

                return $modules[$state] ?? 'Unknown Module';
            });
    }

    /**
     * @return TextColumn
     */
    public static function showUser(): TextColumn
    {
        return TextColumn::make('user.full_name')
            ->label('User')
            ->sortable()
            ->searchable(['first_name', 'middle_name', 'last_name']);
    }

    /**
     * @return IconColumn
     */
    public static function showCreate(): IconColumn
    {
        return IconColumn::make('notify_create')
            ->label('Create')
            ->boolean();
    }

    /**
     * @return IconColumn
     */
    public static function showUpdate(): IconColumn
    {
        return IconColumn::make('notify_update')
            ->label('Update')
            ->boolean();
    }

    /**
     * @return IconColumn
     */
    public static function showDelete(): IconColumn
    {
        return IconColumn::make('notify_delete')
            ->label('Delete')
            ->boolean();
    }

    /**
     * @return IconColumn
     */
    public static function showEmailOption(): IconColumn
    {
        return IconColumn::make('email')
            ->label('Email')
            ->boolean();
    }

    /**
     * @return IconColumn
     */
    public static function showInAppOption(): IconColumn
    {
        return IconColumn::make('in_app')
            ->label('In-App')
            ->boolean();
    }

    /**
     * @return IconColumn
     */
    public static function showSMSOption(): IconColumn
    {
        return IconColumn::make('sms')
            ->label('SMS')
            ->boolean();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Created At')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterBasedOnModule(): SelectFilter
    {
        return SelectFilter::make('notifiable_type')
            ->label('Module')
            ->options([
                'App\Models\ProformaInvoice' => 'Proforma Invoice',
                'App\Models\Order' => 'Order',
                'App\Models\PaymentRequest' => 'Payment Request',
                'App\Models\Payment' => 'Payment',
            ]);
    }
}
