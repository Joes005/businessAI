<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new business owner.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'phone'    => $data['phone'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user'             => $user->load(['businesses', 'currentBusiness']),
            'token'            => $token,
            'has_business'     => false,
            'current_business' => null,
        ], 'Registration successful. Welcome to AI Business Copilot!', 201);
    }

    /**
     * Authenticate owner/staff and generate token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->errorResponse('Invalid email or password credentials.', 401);
        }

        // Clean existing tokens for security
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load(['businesses', 'currentBusiness']);
        $currentBusiness = $user->currentBusiness ?: $user->businesses()->first();

        // Ensure current_business_id is updated if empty but user has a business
        if (!$user->current_business_id && $currentBusiness) {
            $user->update(['current_business_id' => $currentBusiness->id]);
            $user->setRelation('currentBusiness', $currentBusiness);
        }

        return $this->successResponse([
            'user'             => $user,
            'token'            => $token,
            'has_business'     => $user->businesses->count() > 0,
            'current_business' => $currentBusiness,
        ], 'Login successful.');
    }

    /**
     * Logout and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Successfully logged out.');
    }

    /**
     * Get authenticated user profile with business details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['businesses', 'currentBusiness']);
        $currentBusiness = $user->currentBusiness ?: $user->businesses()->first();

        return $this->successResponse([
            'user'             => $user,
            'has_business'     => $user->businesses->count() > 0,
            'current_business' => $currentBusiness,
        ], 'Authenticated user profile loaded.');
    }
}
