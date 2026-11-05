<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Laravel menemukan policy ini otomatis (App\Models\Lead -> App\Policies\LeadPolicy).
 * Pemakaian di controller: $this->authorize('update', $lead);
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $lead->isEditableBy($user);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $lead->isEditableBy($user);
    }
}
