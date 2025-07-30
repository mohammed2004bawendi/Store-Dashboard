<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
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
        return $this->hasRole($user, ['admin', 'support', 'product_manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): Response
    {
        return $this->hasRole($user, ['admin', 'support', 'product_manager'])
            ? Response::allow()
            : Response::deny('You cannot view this product.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $this->hasRole($user, ['admin', 'product_manager'])
            ? Response::allow()
            : Response::deny('You cannot create a new product.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): Response
    {
        return $this->hasRole($user, ['admin', 'product_manager'])
            ? Response::allow()
            : Response::deny('You cannot update this product.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): Response
    {
        return $this->hasRole($user, ['admin', 'product_manager'])
            ? Response::allow()
            : Response::deny('You cannot delete this product.');
    }

    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
