<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        if ($request->filled('email_two_factor_enabled')) {
            $query->where('email_two_factor_enabled', $request->boolean('email_two_factor_enabled'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): UserResource
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'language' => 'nullable|string|in:en,de',
            'gravatar_type' => 'nullable|string|in:mp,identicon,monsterid,wavatar,retro,robohash,blank',
            'email_two_factor_enabled' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'language' => $request->language ?? 'en',
            'gravatar_type' => $request->gravatar_type ?? 'mp',
            'email_two_factor_enabled' => $request->boolean('email_two_factor_enabled', false),
        ]);

        return new UserResource($user);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): UserResource
    {
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'language' => 'nullable|string|in:en,de',
            'gravatar_type' => 'nullable|string|in:mp,identicon,monsterid,wavatar,retro,robohash,blank',
            'email_two_factor_enabled' => 'boolean',
        ]);

        $data = $request->only(['name', 'email', 'language', 'gravatar_type', 'email_two_factor_enabled']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return new UserResource($user);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 400);
        }

        $userName = $user->name;

        // Revoke all API tokens
        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => "User '{$userName}' deleted successfully",
        ]);
    }
}
