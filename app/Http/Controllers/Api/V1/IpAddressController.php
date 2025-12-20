<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\IpAddressResource;
use App\Models\IpAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IpAddressController extends Controller
{
    /**
     * Display a listing of IP addresses.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = IpAddress::with(['group', 'devices']);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('version')) {
            $query->where('version', $request->version);
        }

        if ($request->filled('subnet')) {
            $query->where('subnet', $request->subnet);
        }

        if ($request->filled('available') && $request->available == 'true') {
            $query->where('status', 'available');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('subnet', 'like', "%{$search}%")
                    ->orWhere('gateway', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'ip_address');
        $sortDirection = $request->get('sort_direction', 'asc');

        if ($sortBy === 'ip_address') {
            // Use SQLite compatible IP sorting (basic string comparison)
            $query->orderBy('ip_address', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $ipAddresses = $query->paginate($perPage);

        return IpAddressResource::collection($ipAddresses);
    }

    /**
     * Store a newly created IP address.
     */
    public function store(Request $request): IpAddressResource
    {
        $request->validate([
            'ip_address' => 'required|ip|unique:ip_addresses,ip_address',
            'subnet' => 'nullable|string|max:255',
            'gateway' => 'nullable|ip',
            'status' => 'nullable|in:available,assigned,reserved',
            'version' => 'nullable|in:4,6',
            'group_id' => 'nullable|exists:ip_address_groups,id',
            'description' => 'nullable|string|max:1000',
        ]);

        // Auto-detect IP version if not provided
        $ipVersion = $request->version ?? (filter_var($request->ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 6);

        $ipAddress = IpAddress::create([
            'ip_address' => $request->ip_address,
            'subnet' => $request->subnet,
            'gateway' => $request->gateway,
            'status' => $request->status ?? 'available',
            'version' => $ipVersion,
            'group_id' => $request->group_id,
            'description' => $request->description,
        ]);

        return new IpAddressResource($ipAddress->load(['group', 'devices']));
    }

    /**
     * Display the specified IP address.
     */
    public function show(IpAddress $ipAddress): IpAddressResource
    {
        return new IpAddressResource($ipAddress->load(['group', 'devices']));
    }

    /**
     * Update the specified IP address.
     */
    public function update(Request $request, IpAddress $ipAddress): IpAddressResource
    {
        $request->validate([
            'ip_address' => 'ip|unique:ip_addresses,ip_address,'.$ipAddress->id,
            'subnet' => 'nullable|string|max:255',
            'subnet_mask' => 'nullable|ip',
            'gateway' => 'nullable|ip',
            'status' => 'nullable|in:available,assigned,reserved',
            'ip_version' => 'nullable|in:4,6',
            'group_id' => 'nullable|exists:ip_address_groups,id',
            'hostname' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $ipAddress->update($request->only([
            'ip_address', 'subnet', 'subnet_mask', 'gateway', 'status',
            'ip_version', 'group_id', 'hostname', 'description',
        ]));

        return new IpAddressResource($ipAddress->load(['group', 'devices']));
    }

    /**
     * Remove the specified IP address.
     */
    public function destroy(IpAddress $ipAddress): JsonResponse
    {
        if ($ipAddress->status === 'assigned' && $ipAddress->devices()->exists()) {
            return response()->json([
                'message' => 'Cannot delete IP address that is assigned to devices',
                'assigned_devices' => $ipAddress->devices()->pluck('name'),
            ], 400);
        }

        $ipAddressValue = $ipAddress->ip_address;
        $ipAddress->delete();

        return response()->json([
            'message' => "IP address '{$ipAddressValue}' deleted successfully",
        ]);
    }

    /**
     * Create multiple IP addresses from CIDR range.
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'cidr' => 'required|string',
            'group_id' => 'nullable|exists:ip_address_groups,id',
            'gateway' => 'nullable|ip',
            'description' => 'nullable|string|max:1000',
            'start_from' => 'nullable|integer|min:1',
            'count' => 'nullable|integer|min:1|max:1000',
        ]);

        // Parse CIDR
        if (! preg_match('/^(\d+\.\d+\.\d+\.\d+)\/(\d+)$/', $request->cidr, $matches)) {
            return response()->json([
                'message' => 'Invalid CIDR format. Use format: 192.168.1.0/24',
            ], 400);
        }

        $network = $matches[1];
        $prefixLength = (int) $matches[2];

        $networkLong = ip2long($network);
        $hostMask = (1 << (32 - $prefixLength)) - 1;
        $networkAddress = $networkLong & (~$hostMask);
        $broadcastAddress = $networkAddress | $hostMask;

        $startFrom = $request->start_from ?? 1;
        $count = $request->count ?? ($hostMask - 1); // Exclude network and broadcast
        $maxCount = min($count, 1000); // Safety limit

        $created = 0;
        $skipped = 0;
        $errors = [];

        for ($i = $startFrom; $i <= ($startFrom + $maxCount - 1); $i++) {
            if ($i >= $hostMask) {
                break;
            } // Don't exceed broadcast address

            $ipLong = $networkAddress + $i;
            if ($ipLong >= $broadcastAddress) {
                break;
            } // Don't include broadcast

            $ipAddress = long2ip($ipLong);

            // Skip if IP already exists
            if (IpAddress::where('ip_address', $ipAddress)->exists()) {
                $skipped++;

                continue;
            }

            try {
                IpAddress::create([
                    'ip_address' => $ipAddress,
                    'subnet' => $request->cidr,
                    'gateway' => $request->gateway,
                    'status' => $request->status ?? 'available',
                    'version' => 4,
                    'group_id' => $request->group_id,
                    'description' => $request->description,
                ]);
                $created++;
            } catch (\Exception $e) {
                $errors[] = "Failed to create IP {$ipAddress}: ".$e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Bulk creation completed',
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Update multiple IP addresses.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'ip_address_ids' => 'required|array|min:1',
            'ip_address_ids.*' => 'exists:ip_addresses,id',
            'updates' => 'required|array',
            'updates.gateway' => 'nullable|ip',
            'updates.group_id' => 'nullable|exists:ip_address_groups,id',
            'updates.status' => 'nullable|in:available,assigned,reserved',
            'updates.description' => 'nullable|string|max:1000',
        ]);

        $updates = array_filter($request->updates, function ($value) {
            return $value !== null;
        });

        if (empty($updates)) {
            return response()->json([
                'message' => 'No updates provided',
            ], 400);
        }

        $updated = IpAddress::whereIn('id', $request->ip_address_ids)->update($updates);

        return response()->json([
            'message' => "Successfully updated {$updated} IP addresses",
            'updated_count' => $updated,
        ]);
    }
}
