<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeviceResource;
use App\Models\Device;
use App\Models\IpAddress;
use App\Services\IpAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeviceController extends Controller
{
    private IpAssignmentService $ipAssignmentService;

    public function __construct(IpAssignmentService $ipAssignmentService)
    {
        $this->ipAssignmentService = $ipAssignmentService;
    }

    /**
     * Display a listing of devices.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Device::with(['ipAddresses.group']);

        // Filtering
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$request->location.'%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('hostname', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 items per page
        $devices = $query->paginate($perPage);

        return DeviceResource::collection($devices);
    }

    /**
     * Store a newly created device.
     */
    public function store(Request $request): DeviceResource
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:17',
            'type' => 'nullable|in:server,workstation,laptop,printer,switch,router,firewall,access_point,other',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance',
            'url' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
            'ip_addresses' => 'array',
            'ip_addresses.*' => 'exists:ip_addresses,id',
            'primary_ip' => 'nullable|exists:ip_addresses,id',
        ]);

        $device = Device::create($request->only([
            'name', 'hostname', 'mac_address', 'type', 'location', 'status', 'url', 'description',
        ]));

        // Assign IP addresses if provided
        if ($request->filled('ip_addresses')) {
            foreach ($request->ip_addresses as $ipAddressId) {
                $isPrimary = $request->primary_ip == $ipAddressId;
                $this->ipAssignmentService->assignIpToDevice($device->id, $ipAddressId, $isPrimary);
            }
        }

        return new DeviceResource($device->load('ipAddresses.group'));
    }

    /**
     * Display the specified device.
     */
    public function show(Device $device): DeviceResource
    {
        return new DeviceResource($device->load('ipAddresses.group'));
    }

    /**
     * Update the specified device.
     */
    public function update(Request $request, Device $device): DeviceResource
    {
        $request->validate([
            'name' => 'string|max:255',
            'hostname' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:17',
            'type' => 'nullable|in:server,workstation,laptop,printer,switch,router,firewall,access_point,other',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance',
            'url' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $device->update($request->only([
            'name', 'hostname', 'mac_address', 'type', 'location', 'status', 'url', 'description',
        ]));

        return new DeviceResource($device->load('ipAddresses.group'));
    }

    /**
     * Remove the specified device.
     */
    public function destroy(Device $device): JsonResponse
    {
        $deviceName = $device->name;

        // Unassign all IP addresses
        foreach ($device->ipAddresses as $ipAddress) {
            $this->ipAssignmentService->unassignIpFromDevice($device->id, $ipAddress->id);
        }

        $device->delete();

        return response()->json([
            'message' => "Device '{$deviceName}' deleted successfully",
        ]);
    }

    /**
     * Assign an IP address to a device.
     */
    public function assignIp(Request $request, Device $device): JsonResponse
    {
        $request->validate([
            'ip_address_id' => 'required|exists:ip_addresses,id',
            'is_primary' => 'boolean',
        ]);

        $ipAddress = IpAddress::find($request->ip_address_id);

        if ($ipAddress->status !== 'available') {
            return response()->json([
                'message' => 'IP address is not available for assignment',
                'ip_address' => $ipAddress->ip_address,
            ], 400);
        }

        $this->ipAssignmentService->assignIpToDevice(
            $device->id,
            $request->ip_address_id,
            $request->boolean('is_primary', false)
        );

        return response()->json([
            'message' => 'IP address assigned successfully',
            'device' => new DeviceResource($device->load('ipAddresses.group')),
        ]);
    }

    /**
     * Unassign an IP address from a device.
     */
    public function unassignIp(Device $device, IpAddress $ipAddress): JsonResponse
    {
        $this->ipAssignmentService->unassignIpFromDevice($device->id, $ipAddress->id);

        return response()->json([
            'message' => 'IP address unassigned successfully',
            'device' => new DeviceResource($device->load('ipAddresses.group')),
        ]);
    }
}
