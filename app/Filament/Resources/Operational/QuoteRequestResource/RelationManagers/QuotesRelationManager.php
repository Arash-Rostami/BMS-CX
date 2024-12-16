<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\RelationManagers;

use App\Filament\Resources\Operational\QuoteResource\Pages\Admin;
use App\Filament\Resources\Operational\QuoteResource\Pages\ViewQuote;
use App\Filament\Resources\QuoteResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $viewQuote = resolve(ViewQuote::class);

        return $viewQuote->infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? QuoteResource::getModernLayout($table)
            : QuoteResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([
                Admin::filterCreatedAt(),
                Admin::filterSoftDeletes()
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([])
            ->poll('30s');
    }
}
