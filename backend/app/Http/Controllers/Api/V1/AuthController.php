<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = \App\Models\User::query()
            ->with(['role', 'branch'])
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role', 'branch']);
        return new UserResource($user);
    }
}

