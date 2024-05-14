<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseStatusResource\Pages;
use App\Filament\Resources\PurchaseStatusResource\RelationManagers;
use App\Filament\Resources\Master\PurchaseStatusResource\Pages\Admin;
use App\Models\PurchaseStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PurchaseStatusResource extends Resource
{
    protected static ?string $model = PurchaseStatus::class;

    protected static ?string $navigationIcon = 'heroicon-m-arrow-path-rounded-square';

    protected static ?string $pluralModelLabel = 'Stages';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Stages';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getTitle(),
                Admin::getDescription(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Admin::showTitle(),
                        Admin::showDescription(),
                    ])->columnSpanFull(true),
                ]),
                Admin::showTimeStamp()
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->poll(60)
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
//                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Master\PurchaseStatusResource\Pages\ManagePurchaseStatuses::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }
}
