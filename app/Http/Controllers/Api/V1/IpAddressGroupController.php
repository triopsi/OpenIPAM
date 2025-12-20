<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\IpAddressGroupResource;
use App\Models\IpAddressGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IpAddressGroupController extends Controller
{
    /**
     * Display a listing of IP address groups.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = IpAddressGroup::withCount('ipAddresses');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $groups = $query->paginate($perPage);

        return IpAddressGroupResource::collection($groups);
    }

    /**
     * Store a newly created IP address group.
     */
    public function store(Request $request): IpAddressGroupResource
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ip_address_groups,name',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9a-fA-F]{6}$/',
            'vlan_id' => 'nullable|integer|min:1|max:4094|unique:ip_address_groups,vlan_id',
        ]);

        $group = IpAddressGroup::create($request->only(['name', 'description', 'color', 'vlan_id']));

        return new IpAddressGroupResource($group->loadCount('ipAddresses'));
    }

    /**
     * Display the specified IP address group.
     */
    public function show(IpAddressGroup $ipAddressGroup): IpAddressGroupResource
    {
        return new IpAddressGroupResource($ipAddressGroup->loadCount('ipAddresses'));
    }

    /**
     * Update the specified IP address group.
     */
    public function update(Request $request, IpAddressGroup $ipAddressGroup): IpAddressGroupResource
    {
        $request->validate([
            'name' => 'string|max:255|unique:ip_address_groups,name,'.$ipAddressGroup->id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9a-fA-F]{6}$/',
            'vlan_id' => 'nullable|integer|min:1|max:4094|unique:ip_address_groups,vlan_id,'.$ipAddressGroup->id,
        ]);

        $ipAddressGroup->update($request->only(['name', 'description', 'color', 'vlan_id']));

        return new IpAddressGroupResource($ipAddressGroup->loadCount('ipAddresses'));
    }

    /**
     * Remove the specified IP address group.
     */
    public function destroy(IpAddressGroup $ipAddressGroup): JsonResponse
    {
        if ($ipAddressGroup->ipAddresses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete IP address group that contains IP addresses',
                'ip_addresses_count' => $ipAddressGroup->ipAddresses()->count(),
            ], 400);
        }

        $groupName = $ipAddressGroup->name;
        $ipAddressGroup->delete();

        return response()->json([
            'message' => "IP address group '{$groupName}' deleted successfully",
        ]);
    }
}
