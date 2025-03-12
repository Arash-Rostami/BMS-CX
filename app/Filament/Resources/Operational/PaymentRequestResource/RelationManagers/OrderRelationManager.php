<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\RelationManagers;

use App\Filament\Resources\Operational\OrderResource\Pages\Admin as AdminOrder;
use App\Filament\Resources\Operational\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderRelationManager extends RelationManager
{
    protected static string $relationship = 'order';

    public function scrollLeft()
    {
        $this->dispatch('scrollLeft');
    }

    public function scrollRight()
    {
        $this->dispatch('scrollRight');
    }

    public function toggleFullScreen()
    {
        $this->dispatch('toggleFullScreen');
    }

    public function clearTableSort()
    {
        $this->dispatch('clearTableSort');
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return OrderResource::infolist($infolist);
    }

    public function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? OrderResource::getModernLayout($table)
            : OrderResource::getClassicLayout($table);
    }

    public static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([
                AdminOrder::filterCreatedAt(),
                AdminOrder::filterSoftDeletes()
            ])
            ->recordClasses(fn(Model $record) => isShadeSelected( 'order-table'))
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->url(function (?Model $record) {
                            if (!$record || !$record->id) {
                                return null;
                            }
                            return OrderResource::getUrl('view', ['record' => $record->id]);
                        }, shouldOpenInNewTab: true),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(function (?Model $record) {
                            if (!$record || !$record->id) {
                                return null;
                            }
                            return OrderResource::getUrl('edit', ['record' => $record->id]);
                        }, shouldOpenInNewTab: true)
                ])
            ], position: ActionsPosition::BeforeCells)
            ->headerActions(
                (isModernDesign())
                    ? [ActionGroup::make( (new ListOrders())->getInvisibleTableHeaderActions())]
                    : (new ListOrders())->getInvisibleTableHeaderActions()
            )
            ->poll('30s');
    }
}
