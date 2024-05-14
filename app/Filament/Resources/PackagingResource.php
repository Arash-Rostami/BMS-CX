<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackagingResource\Pages;
use App\Filament\Resources\PackagingResource\RelationManagers;
use App\Filament\Resources\Master\PackagingResource\Pages\Admin;
use App\Models\Packaging;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PackagingResource extends Resource
{
    protected static ?string $model = Packaging::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $pluralModelLabel = 'Packaging';

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
                        Admin::showDescription(),
                    ])->columnSpanFull(true),
                ]),
                Admin::showTImeStamp()
            ])
            ->poll(60)
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
            'index' => Master\PackagingResource\Pages\ManagePackagings::route('/'),
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
