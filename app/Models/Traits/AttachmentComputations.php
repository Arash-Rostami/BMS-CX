<?php

namespace App\Models\Traits;

use App\Models\Attachment;
use Illuminate\Support\HtmlString;

trait AttachmentComputations
{
    public function getCreatedAtBy()
    {
        $creator = optional($this->user)->fullName;
        $time = $this->created_at->format('F d, Y');;
        $message = "Uploaded on {$time} by {$creator}";

        return new HtmlString("<span class='italic'>$message</span>");
    }

    public function isUsedElsewhere(): bool
    {
        return $this->where('file_path', $this->file_path)
            ->where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    public static function getRelatedRecords(Attachment $attachment, $relations = [])
    {
        if (empty($attachment->file_path) && empty($attachment->name)) {
            return collect();
        }

        return self::query()
            ->when($attachment->file_path, function ($query) use ($attachment) {
                $query->where('file_path', $attachment->file_path);
            })
            ->with($relations)
            ->get();
    }
}
