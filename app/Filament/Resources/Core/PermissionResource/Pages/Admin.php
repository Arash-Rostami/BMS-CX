<?php

namespace App\Filament\Resources\Core\PermissionResource\Pages;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Admin
{

    /**
     * @return Select
     */
    public static function getUser(): Select
    {
        return Select::make('user_id')
            ->label('User')
            ->required()
            ->autofocus()
//            ->relationship('user', 'id', fn(Builder $query) => $query->whereNot('role', 'admin'))
            ->relationship('user', 'id')
            ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->fullName}");
    }

    /**
     * @return Select
     */
    public static function getAuthority(): Select
    {
        return Select::make('authority')
            ->options([
                'limited' => 'Limited',
                'unlimited' => 'Full',
            ])
            ->live()
            ->afterStateUpdated(function (Set $set, ?string $state) {
                if ($state == 'unlimited') {
                    $set('permission', 'all');
                    $set('model', 'All');
                }
            });
    }

    /**
     * @return Select
     */
    public static function getAccessLevel(): Select
    {
        return Select::make('permission')
            ->label('Access level')
            ->required(fn(Get $get) => $get('authority') != 'unlimited')
            ->hidden(fn(Get $get) => $get('authority') == 'unlimited')
            ->options([
                'all' => 'All',
                'view' => 'View',
                'create' => 'Create',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'restore' => 'Restore',
            ]);
    }


    /**
     * @return Select
     */
    public static function getModel(): Select
    {
        return Select::make('model')
            ->label('Module')
            ->required(fn(Get $get) => $get('authority') != 'unlimited')
            ->hidden(fn(Get $get) => $get('authority') == 'unlimited')
            ->options(function () {
                $models = self::collectModels();
                $readableModels = self::collectModelNames($models);

                return array_combine($models, $readableModels);
            });
    }

    /**
     * @return Hidden
     */
    public static function getPermissionBasedOnAuthority(): Hidden
    {
        return Hidden::make('permission')
            ->visible(fn(Get $get) => $get('authority') == 'unlimited');
    }

    /**
     * @return Hidden
     */
    public static function getModelBasewdOnAuthority(): Hidden
    {
        return Hidden::make('model')
            ->visible(fn(Get $get) => $get('authority') == 'unlimited');
    }

    /**
     * @return TextColumn
     */
    public static function showUser(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->badge()
            ->color('secondary')
            ->toggleable()
            ->sortable(['first_name'])
            ->searchable(['first_name', 'middle_name', 'last_name']);
    }

    /**
     * @return TextColumn
     */
    public static function showModel(): TextColumn
    {
        return TextColumn::make('model')
            ->label('Module')
            ->badge()
            ->color('primary')
            ->tooltip('permissible Module(s)')
            ->formatStateUsing(fn($state) => self::addSpace(self::changePurchaseStatus($state)))
            ->sortable()
            ->searchable()
            ->toggleable();
    }

    /**
     * @return TextColumn
     */
    public static function showAccessLevel(): TextColumn
    {
        return TextColumn::make('permission')
            ->badge()
            ->label('Access level')
            ->toggleable()
            ->tooltip('permissible Action(s) ')
            ->sortable()
            ->searchable()
            ->formatStateUsing(fn(string $state): string => ucfirst($state));
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }


    /**
     * @return mixed[]
     */
    public static function collectModels(): array
    {
        return collect(File::files(app_path('Models')))
            ->map(fn($file) => pathinfo($file)['filename'])
            ->filter(function ($modelName) {
                $modelClass = "App\\Models\\{$modelName}";
                return !property_exists($modelClass, 'filamentDetection') ||
                    $modelClass::$filamentDetection !== false;
            })->toArray();
    }

    /**
     * @param array $models
     * @return array|array[]|null[]|string[]|\string[][]
     */
    public static function collectModelNames(array $models): array
    {
        return array_map(function ($modelName) {
            $modelName = self::changePurchaseStatus($modelName);
            return self::addSpace($modelName);
        }, $models);
    }

    /**
     * @param mixed $modelName
     * @return mixed|string
     */
    private static function changePurchaseStatus(mixed $modelName): mixed
    {
        if ($modelName === 'PurchaseStatus') {
            $modelName = 'Stages';
        }
        return $modelName;
    }

    /**
     * @param mixed $modelName
     * @return array|string|string[]|null
     */
    private static function addSpace(mixed $modelName): string|array|null
    {
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $modelName);
    }
}
