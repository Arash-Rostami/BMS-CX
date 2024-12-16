<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BeneficiaryResource\Pages;
use App\Filament\Resources\BeneficiaryResource\RelationManagers;
use App\Filament\Resources\Master\BeneficiaryResource\Pages\Admin;
use App\Models\Beneficiary;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
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

class BeneficiaryResource extends Resource
{
    protected static ?string $model = Beneficiary::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Master Data';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Admin::getType() ,
                Admin::getEconomicType(),
                Admin::getName(),
                Admin::getNationalId(),
                Admin::getPhoneNumber(),
                Admin::getAddress(),
                Admin::getExtra(),
                Admin::getVat()
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
                        Admin::showExtra(),
                        Admin::showAddress()
                    ])->columnSpanFull(true),
                ])->collapsible(),
                Admin::showTimeStamp()
            ])
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
            'index' => Master\BeneficiaryResource\Pages\ManageBeneficiaries::route('/'),
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
