<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorResource\Pages;
use App\Filament\Resources\ContractorResource\RelationManagers;
use App\Filament\Resources\Master\ContractorResource\Pages\Admin;
use App\Models\Contractor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ContractorResource extends Resource
{
    protected static ?string $model = Contractor::class;

    protected static ?string $navigationIcon = 'heroicon-m-arrow-up-on-square-stack';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getName(),
                Admin::getDescription(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Admin::showName(),
                        Admin::showDescription(),
                    ])->columnSpanFull(true),
                ]),
                Admin::showTimeStamp()
            ])
            ->filters([ ])
            ->poll(60)
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginated([18])
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
            'index' => Master\ContractorResource\Pages\ManageContractors::route('/'),
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
