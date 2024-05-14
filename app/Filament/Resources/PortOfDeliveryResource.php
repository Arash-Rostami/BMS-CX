<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortOfDeliveryResource\Pages;
use App\Filament\Resources\PortOfDeliveryResource\RelationManagers;
use App\Filament\Resources\Master\PortOfDeliveryResource\Pages\Admin;
use App\Models\PortOfDelivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PortOfDeliveryResource extends Resource
{
    protected static ?string $model = PortOfDelivery::class;

    protected static ?string $navigationIcon = 'heroicon-m-globe-americas';

    protected static ?string $modelLabel = 'Port of Delivery';

    protected static ?string $pluralModelLabel = 'Ports of Delivery';

    protected static ?string $navigationGroup = 'Master Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getName(),
                Admin::getDescription()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Panel::make([
                        Admin::showName(),
                        Admin::showDescription(),
                    ])->columnSpanFull(true),
                ])->space(3),
                Admin::showTimeStamp()
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginated([12])
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
            'index' => Master\PortOfDeliveryResource\Pages\ManagePortOfDeliveries::route('/'),
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
