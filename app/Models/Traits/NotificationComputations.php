<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait NotificationComputations
{
    public function scopeFilterByUserRole(Builder $query, $user): Builder
    {
        if ($user->role === 'admin') return $query;

        $userId = $user->id;
        $search = "%from {$user->first_name} {$user->last_name}%";

        return $query->where(function (Builder $q) use ($userId, $search) {
            $q->where('notifiable_id', $userId)
                ->orWhere('data->title', 'like', $search);
        });
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?? 'Unsent',
        );
    }

    protected function deletedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?? 'Uncleared',
        );
    }

    protected function readAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?? 'Unread',
        );
    }
}
