<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeResource\Pages;
use App\Filament\Resources\GradeResource\RelationManagers;
use App\Filament\Resources\Master\GradeResource\Pages\Admin;
use App\Models\Grade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static ?string $navigationIcon = 'heroicon-m-ellipsis-horizontal-circle';

    protected static ?string $navigationGroup = 'Master Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getProduct(),
                Admin::getName(),
                Admin::getDescription(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Panel::make([
                        Admin::showName(),
                        Admin::showCategory(),
                        Admin::showDescription(),
                    ])->columnSpanFull(true),
                ])->space(4),
                Admin::showTimeStamp(),
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
            ])
            ->groups([
                Tables\Grouping\Group::make('product.name')
                    ->label('Product')
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Master\GradeResource\Pages\ManageGrades::route('/'),
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
