<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Core\PermissionResource\Pages\Admin;
use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\RelationManagers;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Core Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getUser(),
                Admin::getAuthority(),
                Admin::getModel(),
                Admin::getAccessLevel(),
                Admin::getPermissionBasedOnAuthority(),
                Admin::getModelBasewdOnAuthority(),
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
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ])
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showUser(),
                Admin::showModel(),
                Admin::showAccessLevel(),
                Admin::showTimeStamp()
            ])->striped();
    }

    public static function getModernLayout(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Panel::make([
                        Split::make([
                            Stack::make([
                                Admin::showUser(),
                            ]),
                            Stack::make([
                                Admin::showModel(),
                                Admin::showAccessLevel(),
                            ])
                        ])

                    ])->columnSpanFull(true),
                ])->space(4),
                Admin::showTimeStamp()
                    ->alignRight(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 2,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Core\PermissionResource\Pages\ManagePermissions::route('/'),
        ];
    }
}
