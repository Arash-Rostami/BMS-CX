<?php

namespace App\Filament\Resources\Core\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class Settings extends Page
{
    use InteractsWithRecord;

    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.settings';

    public User $user;


    public function mount($record)
    {
        $this->record = $this->resolveRecord($record);
        $this->user = User::find($this->record)->first();
    }




}
