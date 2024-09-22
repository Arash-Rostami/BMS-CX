<?php

namespace App\Filament\Resources\Core\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['info'] = array_merge(data_get($this->form->getRawState(), 'info'), data_get($this->getRecord(), 'info'));

        return $data;
    }
}
