<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait NotificationComputations
{
    protected function deletedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 'Uncleared',
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 'Unsent',
        );
    }

    protected function readAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 'Unread',
        );
    }

    public function scopeFilterByUserRole(Builder $query, $user): Builder
    {
        return $query->when(
            $user->role != 'admin',
            fn (Builder $q) => $q->where('notifiable_id', $user->id)
        );
    }
}
