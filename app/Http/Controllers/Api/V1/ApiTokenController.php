<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ApiTokenResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiTokenController extends Controller
{
    /**
     * Get all API tokens for the authenticated user.
     */
    public function index(): AnonymousResourceCollection
    {
        $user = auth()->user();
        $tokens = $user->tokens()->get();

        return ApiTokenResource::collection($tokens);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
            'never_expires' => 'boolean',
        ]);

        /** @var \App\Models\User $user * */
        $user = auth()->user();

        // Determine expiration
        if ($request->never_expires) {
            $expiresAt = null; // Never expires
        } elseif ($request->expires_at) {
            $expiresAt = Carbon::parse($request->expires_at);
        } else {
            $expiresAt = now()->addYear(); // Default 1 year
        }

        // Abilities (permissions)
        $abilities = $request->abilities ?? ['*']; // Default: all abilities

        $token = $user->createToken($request->name, $abilities, $expiresAt);

        // Add plain text token to the access token for resource response
        $accessToken = $token->accessToken;
        $accessToken->plainTextToken = $token->plainTextToken;

        return response()->json([
            'message' => 'API token created successfully',
            'data' => new ApiTokenResource($accessToken),
        ], 201);
    }

    /**
     * Revoke (delete) an API token.
     */
    public function destroy(string $tokenId): JsonResponse
    {
        /** @var \App\Models\User $user * */
        $user = auth()->user();

        $token = $user->tokens()->where('id', $tokenId)->first();

        if (! $token) {
            return response()->json([
                'message' => 'Token not found',
            ], 404);
        }

        $tokenName = $token->name;
        $token->delete();

        return response()->json([
            'message' => "API token '{$tokenName}' revoked successfully",
        ]);
    }
}
