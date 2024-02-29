<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Filament\Resources\NotificationResource\RelationManagers;
use App\Models\Notification;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;


class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Core Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('notifiable_id')
                    ->label('Recipient')
                    ->options(User::all()->pluck('fullName', 'id'))
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->label('Priority')
                    ->options(['high' => '⬆ Email & In-app', 'low' => '⬇ In-app'])
                    ->required(),
                Forms\Components\Textarea::make('data')
                    ->label('Message')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
//            ->query(fn() => Notification::where('notifiable_id',1))
            ->columns([
                Tables\Columns\TextColumn::make('user.fullName')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('data')
                    ->color('secondary')
                    ->words(6)
                    ->size(TextColumnSize::Small)
                    ->tooltip(function (string $state) {
                        $data = json_decode($state, true);
                        return strip_tags($data['body'] ?? '');
                    })
                    ->formatStateUsing(function (string $state) {
                        $data = json_decode($state, true);
                        return strip_tags($data['body'] ?? '');
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->icon(fn(Model $record) => $record->created_at != 'Unsent' ? 'heroicon-c-shield-check' : 'heroicon-c-shield-exclamation')
                    ->iconColor(fn(Model $record) => $record->created_at != 'Unsent' ? 'success' : 'warning')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('read_at')
                    ->label('Read')
                    ->icon(fn(Model $record) => $record->read_at != 'Unread' ? 'heroicon-c-shield-check' : 'heroicon-c-shield-exclamation')
                    ->iconColor(fn(Model $record) => $record->read_at != 'Unread' ? 'success' : 'danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Cleared')
                    ->icon(fn(Model $record) => $record->deleted_at != 'Uncleared' ? 'heroicon-c-shield-check' : 'heroicon-c-shield-exclamation')
                    ->iconColor(fn(Model $record) => $record->deleted_at != 'Uncleared' ? 'success' : 'warning')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->groups([
                Group::make('user.first_name')
                    ->label('Recipient')
                    ->getTitleFromRecordUsing(fn(Model $record): string => $record->user->fullName)
                    ->getDescriptionFromRecordUsing(fn(Model $record): string => "Acting as {$record->user->role}")
                    ->collapsible(),
                Group::make('data')
                    ->label('Type')
                    ->getTitleFromRecordUsing(function (Model $record) {
                        $jsonData = json_decode($record->data, true);
                        return strip_tags($jsonData['title']) ?? '';
                    })
                    ->groupQueryUsing(fn(Builder $query) => $query->groupBy(json_decode('data.title'))),

            ])
            ->defaultGroup('user.first_name')
            ->defaultSort('created_at', 'desc')
            ->poll(30)
            ->filters([
                SelectFilter::make('notifiable_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Core\NotificationResource\Pages\ManageNotifications::route('/'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return dd($data);

//        return $data;
    }
}
