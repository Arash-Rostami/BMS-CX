<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\Operational\OrderRequestResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\OrderRequestResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\OrderRequestResource\Pages\AdminComponents\Table;
use App\Notifications\FilamentNotification;
use Carbon\Carbon;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class Admin
{

    use Form, Table, Filter;

    protected static array $statusTexts = [
        'pending' => 'Pending',
        'review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'fulfilled' => 'Fulfilled',
    ];

    protected static array $statusIcons = [
        'pending' => 'heroicon-s-clock',
        'review' => 'heroicon-o-chat-bubble-bottom-center-text',
        'approved' => 'heroicon-s-check-circle',
        'rejected' => 'heroicon-s-x-circle',
        'fulfilled' => 'heroicon-s-trophy',
    ];

    protected static array $statusColors = [
        'pending' => 'warning',
        'review' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'fulfilled' => 'secondary',
    ];

    protected static array $statusIconText = [
        'pending' => '⏳ Pending',
        'review' => '⚠ Under Review',
        'approved' => '✅ Approved',
        'rejected' => '❌ Rejected',
        'fulfilled' => '🏁 Fulfilled',
    ];


    public static function send(Model $record)
    {
        $agents = (new CreateOrderRequest())->fetchAgents();

        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $record->product->name,
                'type' => 'delete',
                'module' => 'orderRequest',
                'url' => route('filament.admin.resources.profroma-invoices.index'),
            ]));
        }
    }


    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name;
            $number = $livewire->data['proforma_number'] ?? 'noProFormaNumber';
            // File extension
            $extension = $file->getClientOriginalExtension();

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            // New filename with extension
            $newFileName = "Proforma-{$number}-{$timestamp}-{$name}";

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }

    public static function computeShareFromTotal(Model $record): string
    {
        $price = $record->price ?? 0;
        $quantity = $record->quantity ?? 0;
        $percentage = $record->details['percentage'] ?? 0;
        $total = ($price * $quantity) ?? 0;
        $share = ($total * $percentage) / 100 ?? 0;

        return number_format($share) . ' / ' . number_format($total);
    }
}
