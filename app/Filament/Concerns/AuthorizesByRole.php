<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait AuthorizesByRole
{
    protected static function editorRoles(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->isEditor() ?? false;
    }

    protected static function mediaRoles(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->isMediaManager() ?? false;
    }

    protected static function superAdminOnly(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->isSuperAdmin() ?? false;
    }
}
