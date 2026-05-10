<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages;

use App\Filament\Resources\LedgerEntryResource;
use App\Jobs\RecalculateAccountLedger;
use App\Models\LedgerEntry;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLedgerEntry extends EditRecord
{
    protected static string $resource = LedgerEntryResource::class;
}
