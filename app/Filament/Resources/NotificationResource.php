<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Core\NotificationResource\Pages\Admin;
use App\Filament\Resources\NotificationResource\Pages;
use App\Filament\Resources\NotificationResource\RelationManagers;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;

use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;


class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Operational Data';
    public ?string $tableSortColumn = 'notifiable_id';
    protected static ?int $navigationSort = 10;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getRecipient(),
                Admin::getPriority(),
                Admin::getMessage(),
            ]);
    }


    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);

    }

    private static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->filterByUserRole(auth()->user()))
            ->groups([
                Admin::groupByName(),
                Admin::groupByType()
            ])
            ->paginated([10, 15, 20])
            ->defaultGroup('user.first_name')
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->filters([
                Admin::filterByRecipient()
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


    public static function getModernLayout(Table $table): Table
    {

        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            Admin::showRecipient(),
                            Admin::showMessage(),
                        ]),
                        Stack::make([
                            Admin::showReadTime(),
                            Admin::showClearingTime(),
                        ])
                    ])
                ])->space(4),
                Admin::showCreatedTime()
                    ->alignRight(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 2,
            ]);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showRecipient(),
                Admin::showMessage(),
                Admin::showCreatedTime(),
                Admin::showReadTime(),
                Admin::showClearingTime(),
            ])->striped();
    }


    public static function getPages(): array
    {
        return [
            'index' => Core\NotificationResource\Pages\ManageNotifications::route('/'),
        ];
    }
}
