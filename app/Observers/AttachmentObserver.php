<?php

namespace App\Observers;

use App\Models\Attachment;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AttachmentObserver
{
    public $afterCommit = true;

    /**
     * Handle the Attachment "deleted" event.
     */
    public function deleted(Attachment $attachment)
    {
        $attachment->forceDelete();
        Attachment::flushQueryCache();
    }

    /**
     * Handle the Attachment "deleting" event.
     */
    public function deleting(Attachment $attachment)
    {
        if (!$attachment->isUsedElsewhere() && $attachment->file_path && File::exists(public_path($attachment->file_path))) {
            Storage::disk('filament')->delete($attachment->file_path);
        }
    }

    /**
     * Handle the Attachment "created" event.
     */
    public function saved(Attachment $attachment): void
    {
        Attachment::flushQueryCache();
    }
}
