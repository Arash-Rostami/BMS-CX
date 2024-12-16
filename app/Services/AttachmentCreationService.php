<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Support\Facades\Cache;

class AttachmentCreationService
{
    public static function createFromExisting(int $proformaInvoiceId, $col = 'proforma_invoice_id')
    {
        if (Cache::has('available_attachments')) {
            $oldAttachment = Attachment::find(Cache::get('available_attachments'));

            if ($oldAttachment) {
                Attachment::create([
                    'file_path' => $oldAttachment->file_path,
                    'name' => $oldAttachment->name,
                    'user_id' => auth()->id(),
                    $col => $proformaInvoiceId,
                ]);
            }
            Cache::forget('available_attachments');
        }
    }
}
