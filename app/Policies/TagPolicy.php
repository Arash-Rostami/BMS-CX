<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    /**
     * Determine whether the user can delete the tag.
     */
    public function delete(User $user, Tag $tag)
    {
        return $tag->created_by == auth()->id();
    }

    /**
     * Determine whether the user can create tags.
     */
    public function create(User $user)
    {
        // You can allow all authenticated users to create a tag
        return true;
    }
}
