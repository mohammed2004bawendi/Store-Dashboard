<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
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
    public function view(User $user, Order $order): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot view this order.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot create a new order.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): Response
    {
        return $this->hasRole($user, ['admin', 'product_manager', 'support'])
            ? Response::allow()
            : Response::deny('You cannot update this order.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): Response
    {
        return $this->hasRole($user, ['admin', 'support'])
            ? Response::allow()
            : Response::deny('You cannot delete this order.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
