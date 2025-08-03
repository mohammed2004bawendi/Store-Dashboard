<?php

namespace App\Http\Controllers;

use App\Models\quantityReminder;
use Illuminate\Http\Request;
use App\Models\User;

class QuantityReminderController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        return response()->json([
            'notifications' => $request->user()->notifications()->latest()->get()
        ]);
    }

    /**
     * Mark specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['status' => 'read']);
    }

    /**
     * Not implemented: Show form to create a resource.
     */
    public function create()
    {
        //
    }

    /**
     * Not implemented: Store a newly created resource.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Not implemented: Display a specific resource.
     */
    public function show(quantityReminder $quantityReminder)
    {
        //
    }

    /**
     * Not implemented: Show form to edit a resource.
     */
    public function edit(quantityReminder $quantityReminder)
    {
        //
    }

    /**
     * Not implemented: Update a resource.
     */
    public function update(Request $request, quantityReminder $quantityReminder)
    {
        //
    }

    /**
     * Not implemented: Delete a resource.
     */
    public function destroy(quantityReminder $quantityReminder)
    {
        //
    }
}
