<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Master\ShippingLineResource\Pages\Admin;
use App\Filament\Resources\ShippingLineResource\Pages;
use App\Filament\Resources\ShippingLineResource\RelationManagers;
use App\Models\ShippingLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ShippingLineResource extends Resource
{
    protected static ?string $model = ShippingLine::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $modelLabel = 'Shipping Company';

    protected static ?string $pluralModelLabel = 'Shipping Companies';

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
                Split::make([
                    Panel::make([
                        Admin::showName(),
                        Admin::showDescription()
                    ])->columnSpanFull(true),
                ]),
                Admin::showTimeStamp()
            ])
            ->paginated([12, 24, 36, 48, 'all'])
            ->contentGrid([
                'md' => 2,
                'xl' => 2,
            ])
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
            'index' => Master\ShippingLineResource\Pages\ManageShippingLines::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !isSimpleSidebar();
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
