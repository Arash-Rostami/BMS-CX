<?php

namespace App\Observers;

use App\Models\Permission;
use App\Services\AccessLevel;

class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     */
    public function saved(Permission $permission): void
    {
        if ($permission->user_id) {
            AccessLevel::forgetUserPermissionsCache($permission->user_id);
        }
    }


    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        if ($permission->user_id) {
            AccessLevel::forgetUserPermissionsCache($permission->user_id);
        }
    }
}
