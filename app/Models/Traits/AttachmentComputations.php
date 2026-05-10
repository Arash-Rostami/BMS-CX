<?php


namespace App\Models\Traits;

use App\Models\Attachment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

trait AttachmentComputations
{
    public function getCreatedAtBy(): HtmlString
    {
        $creator = optional($this->user)->fullName ?? 'Unknown user';
        $time = $this->created_at->format('F d, Y');
        $message = e("Uploaded on {$time} by {$creator}");

        return new HtmlString("<span class='italic'>{$message}</span>");
    }

    public static function getRelatedRecords(Attachment $attachment, $relations = [])
    {
        if (empty($attachment->file_path) && empty($attachment->name)) {
            return collect();
        }

        return self::query()
            ->when($attachment->file_path, fn($query) => $query->where('file_path', $attachment->file_path))
            ->with($relations)
            ->get();
    }

    public function isUsedElsewhere(): bool
    {
        $cacheKey = "attachment_duplicate_{$this->id}_{$this->file_path}_{$this->name}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return $this->where('file_path', $this->file_path)
                ->where('name', $this->name)
                ->where('id', '!=', $this->id)
                ->whereNull('deleted_at')
                ->exists();
        });
    }
}
