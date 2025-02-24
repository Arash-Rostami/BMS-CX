<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Master\TargetResource\Pages\Admin;
use App\Filament\Resources\TargetResource\Pages;
use App\Filament\Resources\TargetResource\RelationManagers;
use App\Models\Target;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Linked to Analytics & Statistical Data')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            Admin::getCategory(),
                            Admin::getYear(),
                            Grid::make(4)->schema([
                                Admin::getJanuary(),
                                Admin::getFebruary(),
                                Admin::getMarch(),
                                Admin::getApril(),
                                Admin::getMay(),
                                Admin::getJune(),
                                Admin::getJuly(),
                                Admin::getAugust(),
                                Admin::getSeptember(),
                                Admin::getOctober(),
                                Admin::getNovember(),
                                Admin::getDecember(),
                            ]),
                            Admin::getTotalTargetQuantity(),
                            Admin::getModifiedTargetQuantity(),
                            Admin::getActive(),
                        ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Admin::showCategory(),
                    Admin::showYear(),
                    Admin::showTargetQuantity(),
                    Admin::showModifiedQuantity(),
                    Admin::showActive(),
                ]),
                Split::make([
                    Panel::make([
                        Admin::showMonth(),
                        Admin::showCreator(),
                    ])->columnSpanFull(true),
                ])->collapsible(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 2,
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Master\TargetResource\Pages\ListTargets::route('/'),
            'create' => Master\TargetResource\Pages\CreateTarget::route('/create'),
            'edit' => Master\TargetResource\Pages\EditTarget::route('/{record}/edit'),
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
