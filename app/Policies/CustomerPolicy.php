<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    /**
     * Check if user has one of the allowed roles.
     */
    private function hasRole(User $user, array $roles): bool
    {
        return in_array($user->role, $roles);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['admin', 'support']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot view this customer.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot create a new customer.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot update this customer.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot delete this customer.');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return false;
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return false;
    }
}
