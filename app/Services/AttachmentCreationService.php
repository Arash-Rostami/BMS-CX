<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Support\Facades\Cache;

class AttachmentCreationService
{
    public static function createFromExisting(int $newRecordId, string $col = 'proforma_invoice_id'): void
    {
        $oldAttachmentId = Cache::pull('available_attachments');
        if (!$oldAttachmentId) return;

        $src = Attachment::find((int)$oldAttachmentId, ['file_path', 'name']);
        if (!$src) return;

        $src->replicate()->forceFill(['user_id' => auth()->id(), $col => $newRecordId])->save();
    }
}
