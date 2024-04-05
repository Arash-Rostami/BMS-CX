<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteProviderResource\Pages;
use App\Filament\Resources\QuoteProviderResource\RelationManagers;
use App\Models\QuoteProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\Master\QuoteProviderResource\Pages\Admin;

class QuoteProviderResource extends Resource
{
    protected static ?string $model = QuoteProvider::class;

    protected static ?string $navigationIcon = 'heroicon-c-megaphone';

    protected static ?string $navigationGroup = 'Master Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getTitle(),
                Admin::getName(),
                Admin::getEmail(),
                Admin::getPhoneNumber(),
                Admin::getCompany(),
                Admin::getExtraInfo(),
            ]);
    }

    public static function table(Table $table): Table
    {


        return $table
            ->columns([
                Split::make([
                    Panel::make([
                        Admin::showName(),
                        Admin::showEmail(),
                    ]),
                ])->columnSpanFull(true),
                Split::make([
                    Panel::make([
                        Admin::showCompany(),
                    ])->columnSpanFull(true),
                ])->collapsible(),
                Admin::showTimeStamp()
            ])
            ->poll(30)
            ->filters([])
            ->contentGrid([
                'md' => 2,
                'xl' => 4,
            ])
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
            'index' => Master\QuoteProviderResource\Pages\ManageQuoteProviders::route('/'),
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
