<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Pest\ArchPresets\Custom;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): Response
    {

        return in_array($user->role, ['admin', 'support'])
                ? Response::allow()
                : Response::deny('You can not show the order');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return in_array($user->role, ['admin', 'support'])
                ? Response::allow()
                : Response::deny('You can not creat a new order');
    }

    /**
     * Determine whether the user can update the model.
     */

    /* public function update(User $user, Post $post): Response
{
    return $user->id === $post->user_id
        ? Response::allow()
        : Response::deny('You do not own this post.');
}*/
    public function update(User $user, Order $order): Response
    {
        return in_array($user->role, ['admin', 'product_manager', 'support'])
                ? Response::allow()
                : Response::deny('You can not edit the order');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): Response
    {

        return in_array($user->role, ['admin', 'support'])
                ? Response::allow()
                : Response::deny('You can not delete this order');
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
