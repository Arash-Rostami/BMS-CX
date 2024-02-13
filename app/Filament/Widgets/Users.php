<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class Users extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

//    public function table(Table $table): Table
//    {
//        return $table
//            ->query(
//
//            )
//            ->columns([
//                // ...
//            ]);
//    }
}
