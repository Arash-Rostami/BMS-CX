<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryTermResource\Pages;
use App\Filament\Resources\DeliveryTermResource\RelationManagers;
use App\Filament\Resources\Master\DeliveryTermResource\Pages\Admin;
use App\Models\DeliveryTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DeliveryTermResource extends Resource
{
    protected static ?string $model = DeliveryTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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
                    Stack::make([
                        Admin::showName(),
                    ]),
                ])->space(3),
                Split::make([
                    Panel::make([
                        Admin::showDescription(),
                    ])->columnSpanFull(true),
                ])->collapsible(),
                Admin::showTimeStamp()
            ])
            ->filters([])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('id', 'desc')
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
            'index' => Master\DeliveryTermResource\Pages\ManageDeliveryTerms::route('/'),
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
