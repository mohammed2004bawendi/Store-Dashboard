<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Login method
    public function login(LoginUserRequest $request)
    {
        // Validate request input
        $validated = $request->validated();
        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        // Check if user exists and password is correct
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create API token for the user
        $token = $user->createToken('api-token')->plainTextToken;

        // Return token in JSON response
        return response()->json([
            'token' => $token,
        ]);
    }

    // Logout method
    public function logout(Request $request)
    {
        // Delete all tokens for the authenticated user
        $request->user()->tokens()->delete();

        // Return logout confirmation message
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
