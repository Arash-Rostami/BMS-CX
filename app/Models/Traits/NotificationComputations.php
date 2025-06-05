<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;


trait NotificationComputations
{

    public function getDeletedAtAttribute()
    {
        if (is_null($this->attributes['deleted_at'])) {
            return 'Uncleared';
        }

        return $this->attributes['deleted_at'];
    }

    public function getCreatedAtAttribute()
    {
        if (is_null($this->attributes['created_at'])) {
            return 'Unsent';
        }

        return $this->attributes['created_at'];
    }

    public function getReadAtAttribute()
    {
        if (is_null($this->attributes['read_at'])) {
            return 'Unread';
        }

        return $this->attributes['read_at'];
    }

    public function scopeFilterByUserRole(Builder $query, $user): Builder
    {
        if ($user->role != 'admin') {
            return $query->where('notifiable_id', $user->id);
        }

        return $query;
    }
}
