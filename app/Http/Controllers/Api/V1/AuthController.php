<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and create API token.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'token_name' => 'string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create token with name and expiration
        $tokenName = $request->token_name ?? 'API Token';
        $expiresAt = $request->expires_at ? now()->parse($request->expires_at) : now()->addYear();

        $token = $user->createToken($tokenName, ['*'], $expiresAt);

        return response()->json([
            'message' => 'Authentication successful',
            'token' => $token->plainTextToken,
            'token_name' => $tokenName,
            'expires_at' => $expiresAt->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'language' => $user->language,
            ],
        ], 200);
    }

    /**
     * Register new user (optional - remove if not needed).
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'language' => 'string|in:en,de',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'language' => $request->language ?? 'en',
            'gravatar_type' => 'mp',
            'email_two_factor_enabled' => false,
        ]);

        $token = $user->createToken('Registration Token', ['*'], now()->addYear());

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'language' => $user->language,
            ],
        ], 201);
    }

    /**
     * Get authenticated user information.
     */
    public function user(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'language' => $user->language,
                'email_two_factor_enabled' => $user->email_two_factor_enabled,
                'gravatar_type' => $user->gravatar_type,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Logout user by revoking current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}
