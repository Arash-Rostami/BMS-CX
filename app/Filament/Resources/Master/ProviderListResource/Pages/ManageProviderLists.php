<?php

namespace App\Filament\Resources\Master\ProviderListResource\Pages;

use App\Filament\Resources\ProviderListResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageProviderLists extends ManageRecords
{
    protected static string $resource = ProviderListResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles')
                ->createAnother(false)
                ->using(fn(array $data, string $model): Model => Admin::createRecord($model, $data)),
        ];
    }
}
