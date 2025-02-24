<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;


use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents\Table;
use App\Models\Attachment;
use App\Models\PaymentRequest;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentDeletionService;
use App\Services\Notification\ProformaInvoiceService;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
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
        'rejected' => 'Rejected/Cancelled',
        'fulfilled' => 'Completed',
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
        'rejected' => '❌ Declined/Cancelled',
        'fulfilled' => '🏁 Completed',
    ];


    public static function send(Model $record)
    {
        $service = new ProformaInvoiceService();
        $service->notifyAgents($record, 'delete');
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
            $randomString = Str::random(5);

            // New filename with extension
            $newFileName = "PI-{$number}-{$timestamp}-{$randomString}-{$name}";

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
        persistReferenceNumber($replica, 'PI');
        $service = new ProformaInvoiceService();
        $service->notifyAgents($replica);
    }

    public static function separateRecordsIntoDeletableAndNonDeletable(Collection $records): void
    {
        $recordsToDelete = $records->filter(function ($record) {
            return $record->activeApprovedPaymentRequests->isEmpty() && $record->activeOrders->isEmpty();
        });
        $recordsNotDeleted = $records->diff($recordsToDelete);

        // Delete the records that have no paymentRequests
        $recordsToDelete->each->delete();
        $recordsToDelete->each(fn(Model $selectedRecord) => Admin::send($selectedRecord));

        if ($recordsNotDeleted->isNotEmpty()) {
            $recordReferences = $recordsNotDeleted->pluck('reference_number')->join(', ');
            Notification::make()
                ->title('Some records were not deleted')
                ->body("The following records could not be deleted because they have active orders or payment requests: $recordReferences.")
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Records deleted successfully')
                ->success()
                ->send();
        }
    }
}
