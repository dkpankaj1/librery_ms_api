<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Authenticate a user and issue a Sanctum access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', null, 401);
        }

        if (! $user->isActive()) {
            return $this->errorResponse('Account is inactive or suspended', null, 403);
        }

        if (! $user->isAccountValid()) {
            return $this->errorResponse('Account has expired', null, 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->successResponse([
            'user'    => $user->load('library'),
            'token'   => $token,
        ], 'Login successful');
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        return $this->successResponse(Auth::user()->load('library'), 'Authenticated user');
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();

        $user->update($request->validated());

        return $this->successResponse($user, 'Profile updated');
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}
