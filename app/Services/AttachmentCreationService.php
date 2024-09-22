<?php

namespace App\Services;

use App\Models\Attachment;

class AttachmentCreationService
{
    public static function createFromExisting(array $mainData, int $proformaInvoiceId, $col = 'proforma_invoice_id'): void
    {
        if (
            !isset($mainData['use_existing_attachments'], $mainData['available_attachments'], $mainData['source_proforma_invoice'])
            ||
            !$mainData['use_existing_attachments']
        ) {
            return;
        }

        $oldAttachment = Attachment::find($mainData['available_attachments']);

        if (!$oldAttachment) {
            return;
        }

        Attachment::create([
            'file_path' => $oldAttachment->file_path,
            'name' => $oldAttachment->name,
            'user_id' => auth()->id(),
            $col => $proformaInvoiceId,
        ]);
    }
}
