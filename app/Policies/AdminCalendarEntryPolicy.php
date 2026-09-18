<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AdminCalendarEntry;
use App\Models\User;

class AdminCalendarEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canUsePanel($user);
    }

    public function view(User $user, AdminCalendarEntry $adminCalendarEntry): bool
    {
        return $adminCalendarEntry->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $this->canUsePanel($user);
    }

    public function update(User $user, AdminCalendarEntry $adminCalendarEntry): bool
    {
        return $adminCalendarEntry->isOwnedBy($user);
    }

    public function delete(User $user, AdminCalendarEntry $adminCalendarEntry): bool
    {
        return $adminCalendarEntry->isOwnedBy($user);
    }

    private function canUsePanel(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Editor, UserRole::Media], true);
    }
}
