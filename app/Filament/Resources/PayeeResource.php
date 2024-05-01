<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Master\PayeeResource\Pages\Admin;
use App\Filament\Resources\PayeeResource\Pages;
use App\Filament\Resources\PayeeResource\RelationManagers;
use App\Models\Payee;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PayeeResource extends Resource
{
    protected static ?string $model = Payee::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Master Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::class::getType(),
                Admin::class::getEconomicType(),
                Admin::class::getName(),
                Admin::class::getNationalId(),
                Admin::class::getPhoneNumber(),
                Admin::class::getPostalCode(),
                Admin::class::getAddress(),
                Admin::class::getVat()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Stack::make([
                        Admin::showFullName(),
                        Split::make([
                            Admin::showNationalId(),
                            Admin::showEconomicCode(),
                        ])
                    ]),
                ])->space(3),
                Split::make([
                    Panel::make([
                        Admin::showPhoneNumber(),
                        Admin::showAddressZipCode()
                    ])->columnSpanFull(true),
                ])->collapsible(),
                Admin::showTimeStamp()
            ])
            ->poll(30)
            ->defaultSort('name', 'asc')
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
            'index' => Master\PayeeResource\Pages\ManagePayees::route('/'),
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
