<?php

namespace App\Http\Controllers;

use App\Models\quantityReminder;
use Illuminate\Http\Request;
use App\Models\User;


class QuantityReminderController extends Controller
{

    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        return response()->json([
        'notifications' => $request->user()->notifications()->latest()->get()
    ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['status' => 'read']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(quantityReminder $quantityReminder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(quantityReminder $quantityReminder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, quantityReminder $quantityReminder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(quantityReminder $quantityReminder)
    {
        //
    }
}
