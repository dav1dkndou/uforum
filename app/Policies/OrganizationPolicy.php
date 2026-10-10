<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function create(User $user)
    {
        return $user->role === 'kemahasiswaan';
    }

    public function update(User $user, Organization $organization)
    {
        return $user->role === 'kemahasiswaan';
    }

    /**
     * Toggle status is only allowed for non-BEM organizations
     * and only by Kemahasiswaan.
     */
    public function toggleStatus(User $user, Organization $organization)
    {
        if ($user->role !== 'kemahasiswaan') {
            return false;
        }

        // BEM accounts cannot be deactivated
        if ($organization->kategori === 'BEM') {
            return false;
        }

        return true;
    }

    public function delete(User $user, Organization $organization)
    {
        return $user->role === 'kemahasiswaan';
    }
}
