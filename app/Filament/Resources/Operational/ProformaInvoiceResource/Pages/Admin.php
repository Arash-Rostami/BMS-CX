<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;


use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents\Table;
use App\Models\Attachment;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentDeletionService;
use App\Services\ProformaInvoiceService;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
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
        $agents = (new ProformaInvoiceService())->fetchAgents();

        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $record->proforma_number . ' (' . $record->reference_number . ')',
                'type' => 'delete',
                'module' => 'proformaInvoice',
                'url' => route('filament.admin.resources.proforma-invoices.index'),
            ]));
        }
    }


    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name ?? 'NO-NAME-GIVEN';
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
        $percentage = $record->percentage ?? 0;
        $total = ($price * $quantity) ?? 0;
        $share = ($total * $percentage) / 100 ?? 0;

        return number_format($share) . ' / ' . number_format($total);
    }

    public static function syncProformaInvoice(Model $replica): void
    {
        $service = new ProformaInvoiceService();
        $agents = $service->fetchAgents();
        $service->persistReferenceNumber($replica);
        $service->notifyAgents($replica, $agents);
    }
}
