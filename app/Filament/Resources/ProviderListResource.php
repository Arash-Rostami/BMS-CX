<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Master\ProviderListResource\Pages\Admin;
use App\Filament\Resources\ProviderListResource\Pages;
use App\Filament\Resources\ProviderListResource\RelationManagers;
use App\Models\ProviderList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProviderListResource extends Resource
{
    protected static ?string $model = ProviderList::class;

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';

    protected static ?string $navigationGroup = 'Master Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getName(),
                Admin::getRecipients(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Admin::viewName(),
                Admin::viewRecipients(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Panel::make([
                        Admin::showName(),
                        Admin::showRecipients(),
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
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->using(fn(array $data, string $model, Model $record): Model => Admin::updateRecord($record, $data)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Master\ProviderListResource\Pages\ManageProviderLists::route('/'),
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
