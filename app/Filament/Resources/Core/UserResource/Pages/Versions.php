<?php

namespace App\Filament\Resources\Core\UserResource\Pages;

//use App\Filament\Resources\Core\UserResource;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\Page;

class Versions extends Page
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Version Timeline';

    protected static string $view = 'filament.resources.core.user-resource.pages.versions';
}
