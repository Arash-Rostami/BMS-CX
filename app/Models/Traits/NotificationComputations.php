<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait NotificationComputations
{
    public function scopeFilterByUserRole(Builder $query, $user): Builder
    {
        return $query->when(
            $user->role !== 'admin',
            fn(Builder $q) => $q->where(function ($subQuery) use ($user) {
                $fullName = $user->first_name . ' ' . $user->last_name;
                // receiver or sender
                $subQuery->where('notifiable_id', $user->id)->orWhere('data->title', 'like', "%from {$fullName}%");
            })
        );
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
