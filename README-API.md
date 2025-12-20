# REST API Documentation

This documentation describes the REST API endpoints for the inventory management system.

## Base URL

All API endpoints are prefixed with `/api/v1/`

## Authentication

This API uses Laravel Sanctum for token-based authentication. You need to obtain an API token to access protected endpoints.

### Token Management

#### Login and Get Token

**POST** `/api/v1/auth/login`

Login with email and password to receive an API token.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "your-password",
    "token_name": "My API Token (optional)",
    "expires_at": "2025-12-31T23:59:59Z (optional)",
    "never_expires": false
}
```

**Response:**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "language": "en"
    },
    "token": "1|plainTextTokenString"
}
```

#### Register New User

**POST** `/api/v1/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "secure-password",
    "password_confirmation": "secure-password",
    "language": "en"
}
```

**Response:**
```json
{
    "message": "Registration successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "language": "en"
    },
    "token": "2|plainTextTokenString"
}
```

#### Get Current User

**GET** `/api/v1/auth/user`

Get the currently authenticated user's information.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "language": "en",
    "gravatar_type": "mp",
    "email_two_factor_enabled": false
}
```

#### Logout

**POST** `/api/v1/auth/logout`

Revoke the current API token.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "Logout successful"
}
```

### API Token Management

#### List API Tokens

**GET** `/api/v1/tokens`

Get all API tokens for the authenticated user.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "My API Token",
            "abilities": ["*"],
            "expires_at": "2025-12-31T23:59:59.000000Z",
            "last_used_at": "2024-12-12T10:30:00.000000Z",
            "created_at": "2024-12-12T10:00:00.000000Z",
            "is_expired": false
        }
    ]
}
```

#### Create API Token

**POST** `/api/v1/tokens`

Create a new API token.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Request Body:**
```json
{
    "name": "My API Token",
    "abilities": ["*"],
    "expires_at": "2025-12-31T23:59:59Z",
    "never_expires": false
}
```

**Response:**
```json
{
    "message": "API token created successfully",
    "data": {
        "id": 2,
        "name": "My API Token",
        "abilities": ["*"],
        "expires_at": "2025-12-31T23:59:59.000000Z",
        "created_at": "2024-12-12T10:00:00.000000Z",
        "plain_text_token": "3|newPlainTextTokenString"
    }
}
```

#### Revoke API Token

**DELETE** `/api/v1/tokens/{token_id}`

Revoke (delete) a specific API token.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "API token revoked successfully"
}
```

## Devices Management

### List Devices

**GET** `/api/v1/devices`

Get a paginated list of devices with optional filtering and sorting.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `type` (string): Filter by device type
- `status` (string): Filter by device status
- `location` (string): Filter by location (partial match)
- `search` (string): Search in name, hostname, or description
- `sort_by` (string): Sort field (default: name)
- `sort_direction` (string): Sort direction (asc/desc, default: asc)

**Example Request:**
```
GET /api/v1/devices?type=server&status=active&page=1&per_page=20
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Web Server 01",
            "type": "server",
            "description": "Main web server",
            "location": "Data Center A",
            "department": "IT",
            "serial_number": "SN123456",
            "asset_tag": "IT-001",
            "status": "active",
            "purchase_date": "2023-01-15",
            "warranty_until": "2026-01-15",
            "notes": "Primary web server",
            "is_active": true,
            "created_at": "2024-12-12T10:00:00.000000Z",
            "updated_at": "2024-12-12T10:00:00.000000Z",
            "ip_addresses": [
                {
                    "id": 1,
                    "address": "192.168.1.100",
                    "subnet_mask": "255.255.255.0",
                    "type": "static"
                }
            ],
            "ip_addresses_count": 1
        }
    ],
    "links": {
        "first": "http://example.com/api/v1/devices?page=1",
        "last": "http://example.com/api/v1/devices?page=3",
        "prev": null,
        "next": "http://example.com/api/v1/devices?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 20,
        "to": 20,
        "total": 45
    }
}
```

### Get Single Device

**GET** `/api/v1/devices/{device_id}`

Get a specific device by ID.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Web Server 01",
        "type": "server",
        "description": "Main web server",
        "location": "Data Center A",
        "department": "IT",
        "serial_number": "SN123456",
        "asset_tag": "IT-001",
        "status": "active",
        "purchase_date": "2023-01-15",
        "warranty_until": "2026-01-15",
        "notes": "Primary web server",
        "is_active": true,
        "created_at": "2024-12-12T10:00:00.000000Z",
        "updated_at": "2024-12-12T10:00:00.000000Z",
        "ip_addresses": [...]
    }
}
```

### Create Device

**POST** `/api/v1/devices`

Create a new device.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "New Server",
    "type": "server",
    "description": "Description of the device",
    "location": "Data Center B",
    "department": "IT",
    "serial_number": "SN789012",
    "asset_tag": "IT-002",
    "status": "active",
    "purchase_date": "2024-01-15",
    "warranty_until": "2027-01-15",
    "notes": "Additional notes",
    "is_active": true
}
```

**Required Fields:**
- `name` (string, max 255): Device name
- `type` (string): Device type

**Optional Fields:**
- `description` (string): Device description
- `location` (string): Physical location
- `department` (string): Department
- `serial_number` (string): Serial number
- `asset_tag` (string): Asset tag
- `status` (string): Device status
- `purchase_date` (date): Purchase date
- `warranty_until` (date): Warranty expiration
- `notes` (text): Additional notes
- `is_active` (boolean): Active status (default: true)

**Response:**
```json
{
    "data": {
        "id": 2,
        "name": "New Server",
        "type": "server",
        // ... other fields
        "created_at": "2024-12-12T11:00:00.000000Z",
        "updated_at": "2024-12-12T11:00:00.000000Z"
    }
}
```

### Update Device

**PUT/PATCH** `/api/v1/devices/{device_id}`

Update an existing device.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Updated Server Name",
    "status": "maintenance",
    "notes": "Server is under maintenance"
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Updated Server Name",
        "status": "maintenance",
        // ... other fields
        "updated_at": "2024-12-12T12:00:00.000000Z"
    }
}
```

### Delete Device

**DELETE** `/api/v1/devices/{device_id}`

Delete a device.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "Device 'Web Server 01' deleted successfully"
}
```

### Assign IP to Device

**POST** `/api/v1/devices/{device_id}/assign-ip`

Assign an IP address to a device.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "ip_address_id": 5
}
```

**Response:**
```json
{
    "message": "IP address 192.168.1.100 assigned to device Web Server 01 successfully"
}
```

### Unassign IP from Device

**DELETE** `/api/v1/devices/{device_id}/unassign-ip/{ip_address_id}`

Unassign an IP address from a device.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "IP address 192.168.1.100 unassigned from device Web Server 01 successfully"
}
```

## IP Addresses Management

### List IP Addresses

**GET** `/api/v1/ip-addresses`

Get a paginated list of IP addresses with optional filtering and sorting.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `type` (string): Filter by IP type (static/dhcp/reserved)
- `status` (string): Filter by IP status
- `group_id` (integer): Filter by IP address group
- `available` (boolean): Filter available/assigned IPs
- `search` (string): Search in address or description
- `sort_by` (string): Sort field (default: address)
- `sort_direction` (string): Sort direction (asc/desc)

**Example Request:**
```
GET /api/v1/ip-addresses?type=static&status=active&available=true&page=1
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "address": "192.168.1.100",
            "subnet_mask": "255.255.255.0",
            "gateway": "192.168.1.1",
            "dns_primary": "8.8.8.8",
            "dns_secondary": "8.8.4.4",
            "type": "static",
            "status": "active",
            "group_id": 1,
            "description": "Web server IP",
            "notes": "Primary web server",
            "is_active": true,
            "reserved_until": null,
            "created_at": "2024-12-12T10:00:00.000000Z",
            "updated_at": "2024-12-12T10:00:00.000000Z",
            "group": {
                "id": 1,
                "name": "Production Servers",
                "description": "Production server IP range"
            },
            "devices": [],
            "devices_count": 0
        }
    ],
    // ... pagination metadata
}
```

### Get Single IP Address

**GET** `/api/v1/ip-addresses/{ip_id}`

Get a specific IP address by ID.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "address": "192.168.1.100",
        // ... all IP address fields
        "group": { ... },
        "devices": [ ... ]
    }
}
```

### Create IP Address

**POST** `/api/v1/ip-addresses`

Create a new IP address.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "address": "192.168.1.101",
    "subnet_mask": "255.255.255.0",
    "gateway": "192.168.1.1",
    "dns_primary": "8.8.8.8",
    "dns_secondary": "8.8.4.4",
    "type": "static",
    "status": "active",
    "group_id": 1,
    "description": "New server IP",
    "notes": "Allocated for new server",
    "is_active": true,
    "reserved_until": "2024-12-31T23:59:59Z"
}
```

**Required Fields:**
- `address` (IP address): IP address (must be unique)

**Optional Fields:**
- `subnet_mask` (IP address): Subnet mask
- `gateway` (IP address): Default gateway
- `dns_primary` (IP address): Primary DNS server
- `dns_secondary` (IP address): Secondary DNS server
- `type` (string): IP type (static/dhcp/reserved)
- `status` (string): IP status
- `group_id` (integer): IP address group ID
- `description` (string): Description
- `notes` (text): Additional notes
- `is_active` (boolean): Active status
- `reserved_until` (datetime): Reservation expiration

**Response:**
```json
{
    "data": {
        "id": 2,
        "address": "192.168.1.101",
        // ... all fields
        "created_at": "2024-12-12T11:00:00.000000Z"
    }
}
```

### Update IP Address

**PUT/PATCH** `/api/v1/ip-addresses/{ip_id}`

Update an existing IP address.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "status": "reserved",
    "reserved_until": "2024-12-31T23:59:59Z",
    "description": "Reserved for maintenance"
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "address": "192.168.1.100",
        "status": "reserved",
        // ... other fields
        "updated_at": "2024-12-12T12:00:00.000000Z"
    }
}
```

### Delete IP Address

**DELETE** `/api/v1/ip-addresses/{ip_id}`

Delete an IP address.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "IP address 192.168.1.100 deleted successfully"
}
```

### Bulk Create IP Addresses

**POST** `/api/v1/ip-addresses/bulk-create`

Create multiple IP addresses from a CIDR range.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "cidr": "192.168.2.0/24",
    "group_id": 2,
    "type": "static",
    "status": "available",
    "description": "Bulk created from CIDR",
    "exclude_network": true,
    "exclude_broadcast": true,
    "exclude_gateway": true,
    "gateway": "192.168.2.1",
    "dns_primary": "8.8.8.8",
    "dns_secondary": "8.8.4.4"
}
```

**Required Fields:**
- `cidr` (string): CIDR notation (e.g., "192.168.1.0/24")

**Optional Fields:**
- `group_id` (integer): IP address group ID
- `type` (string): IP type for all created addresses
- `status` (string): Status for all created addresses
- `description` (string): Description for all created addresses
- `exclude_network` (boolean): Exclude network address (default: true)
- `exclude_broadcast` (boolean): Exclude broadcast address (default: true)
- `exclude_gateway` (boolean): Exclude gateway address (default: true)
- `gateway` (IP address): Gateway for all created addresses
- `dns_primary` (IP address): Primary DNS for all created addresses
- `dns_secondary` (IP address): Secondary DNS for all created addresses

**Response:**
```json
{
    "message": "Successfully created 253 IP addresses from CIDR 192.168.2.0/24",
    "created_count": 253,
    "skipped_count": 1
}
```

### Bulk Update IP Addresses

**PUT** `/api/v1/ip-addresses/bulk-update`

Update multiple IP addresses at once.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "ip_address_ids": [1, 2, 3, 4, 5],
    "updates": {
        "status": "maintenance",
        "group_id": 2,
        "description": "Under maintenance",
        "reserved_until": "2024-12-31T23:59:59Z"
    }
}
```

**Required Fields:**
- `ip_address_ids` (array): Array of IP address IDs to update
- `updates` (object): Fields to update

**Response:**
```json
{
    "message": "Successfully updated 5 IP addresses",
    "updated_count": 5
}
```

## IP Address Groups Management

### List IP Address Groups

**GET** `/api/v1/ip-address-groups`

Get a paginated list of IP address groups.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `search` (string): Search in name or description
- `sort_by` (string): Sort field (default: name)
- `sort_direction` (string): Sort direction (asc/desc)

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Production Servers",
            "description": "IP range for production servers",
            "color": "#007bff",
            "is_active": true,
            "created_at": "2024-12-12T10:00:00.000000Z",
            "updated_at": "2024-12-12T10:00:00.000000Z",
            "ip_addresses_count": 50
        }
    ],
    // ... pagination metadata
}
```

### Get Single IP Address Group

**GET** `/api/v1/ip-address-groups/{group_id}`

Get a specific IP address group by ID.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Production Servers",
        "description": "IP range for production servers",
        "color": "#007bff",
        "is_active": true,
        "created_at": "2024-12-12T10:00:00.000000Z",
        "updated_at": "2024-12-12T10:00:00.000000Z",
        "ip_addresses": [
            // ... IP addresses in this group
        ],
        "ip_addresses_count": 50
    }
}
```

### Create IP Address Group

**POST** `/api/v1/ip-address-groups`

Create a new IP address group.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Development Servers",
    "description": "IP range for development servers",
    "color": "#28a745",
    "is_active": true
}
```

**Required Fields:**
- `name` (string, max 255): Group name

**Optional Fields:**
- `description` (text): Group description
- `color` (string): Hex color code for UI display
- `is_active` (boolean): Active status (default: true)

**Response:**
```json
{
    "data": {
        "id": 2,
        "name": "Development Servers",
        "description": "IP range for development servers",
        "color": "#28a745",
        "is_active": true,
        "created_at": "2024-12-12T11:00:00.000000Z",
        "updated_at": "2024-12-12T11:00:00.000000Z",
        "ip_addresses_count": 0
    }
}
```

### Update IP Address Group

**PUT/PATCH** `/api/v1/ip-address-groups/{group_id}`

Update an existing IP address group.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Updated Group Name",
    "description": "Updated description",
    "color": "#ffc107"
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Updated Group Name",
        "description": "Updated description",
        "color": "#ffc107",
        // ... other fields
        "updated_at": "2024-12-12T12:00:00.000000Z"
    }
}
```

### Delete IP Address Group

**DELETE** `/api/v1/ip-address-groups/{group_id}`

Delete an IP address group.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "IP address group 'Production Servers' deleted successfully"
}
```

## Users Management

### List Users

**GET** `/api/v1/users`

Get a paginated list of users (admin access required).

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `search` (string): Search in name or email
- `language` (string): Filter by language
- `email_two_factor_enabled` (boolean): Filter by 2FA status
- `sort_by` (string): Sort field (default: name)
- `sort_direction` (string): Sort direction (asc/desc)

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "language": "en",
            "gravatar_type": "mp",
            "email_two_factor_enabled": false,
            "email_verified_at": "2024-12-12T10:00:00.000000Z",
            "created_at": "2024-12-12T10:00:00.000000Z",
            "updated_at": "2024-12-12T10:00:00.000000Z"
        }
    ],
    // ... pagination metadata
}
```

### Get Single User

**GET** `/api/v1/users/{user_id}`

Get a specific user by ID.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "language": "en",
        "gravatar_type": "mp",
        "email_two_factor_enabled": false,
        "email_verified_at": "2024-12-12T10:00:00.000000Z",
        "created_at": "2024-12-12T10:00:00.000000Z",
        "updated_at": "2024-12-12T10:00:00.000000Z"
    }
}
```

### Create User

**POST** `/api/v1/users`

Create a new user account.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "secure-password",
    "language": "en",
    "gravatar_type": "identicon",
    "email_two_factor_enabled": false
}
```

**Required Fields:**
- `name` (string, max 255): User's full name
- `email` (string): Email address (must be unique)
- `password` (string, min 8): Password

**Optional Fields:**
- `language` (string): Language code (en/de, default: en)
- `gravatar_type` (string): Gravatar type
- `email_two_factor_enabled` (boolean): Enable 2FA (default: false)

**Response:**
```json
{
    "data": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "language": "en",
        "gravatar_type": "identicon",
        "email_two_factor_enabled": false,
        "created_at": "2024-12-12T11:00:00.000000Z",
        "updated_at": "2024-12-12T11:00:00.000000Z"
    }
}
```

### Update User

**PUT/PATCH** `/api/v1/users/{user_id}`

Update an existing user.

**Headers:**
```
Authorization: Bearer {your-api-token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "password": "new-secure-password",
    "language": "de",
    "email_two_factor_enabled": true
}
```

**Response:**
```json
{
    "data": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "language": "de",
        "email_two_factor_enabled": true,
        // ... other fields
        "updated_at": "2024-12-12T12:00:00.000000Z"
    }
}
```

### Delete User

**DELETE** `/api/v1/users/{user_id}`

Delete a user account.

**Headers:**
```
Authorization: Bearer {your-api-token}
```

**Response:**
```json
{
    "message": "User 'Jane Smith' deleted successfully"
}
```

## Error Responses

### Standard Error Format

All API errors follow a consistent format:

```json
{
    "message": "Error description",
    "errors": {
        "field_name": [
            "Specific validation error message"
        ]
    }
}
```

### HTTP Status Codes

- `200` - OK: Request successful
- `201` - Created: Resource created successfully
- `400` - Bad Request: Invalid request data
- `401` - Unauthorized: Authentication required or invalid token
- `403` - Forbidden: Insufficient permissions
- `404` - Not Found: Resource not found
- `422` - Unprocessable Entity: Validation errors
- `429` - Too Many Requests: Rate limit exceeded
- `500` - Internal Server Error: Server error

### Common Error Examples

#### Authentication Error
```json
{
    "message": "Unauthenticated."
}
```

#### Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password must be at least 8 characters."
        ]
    }
}
```

#### Resource Not Found
```json
{
    "message": "Device not found."
}
```

#### Rate Limit Exceeded
```json
{
    "message": "Too Many Attempts."
}
```

## Rate Limiting

API requests are rate-limited to prevent abuse:
- **60 requests per minute** for authenticated users
- **10 requests per minute** for unauthenticated requests

Rate limit information is included in response headers:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Timestamp when rate limit resets

## Best Practices

### Security
- Store API tokens securely and never expose them in client-side code
- Use HTTPS for all API requests
- Implement proper token rotation policies
- Set appropriate token expiration times

### Performance
- Use pagination for large result sets
- Implement proper caching strategies
- Use filtering and sorting parameters to reduce data transfer
- Monitor rate limits and implement retry logic

### Error Handling
- Always check HTTP status codes
- Parse error response messages for user feedback
- Implement proper error logging and monitoring
- Use appropriate retry strategies for transient errors

### Data Validation
- Validate all input data before sending requests
- Handle validation errors gracefully
- Use proper data types for all fields
- Follow API field requirements and constraints

### Token Management
- Create tokens with minimal required permissions
- Set appropriate expiration times (default: 1 year)
- Regularly audit and revoke unused tokens
- Use descriptive token names for easier management

## Examples

### Complete Authentication Flow

```bash
# 1. Login to get API token
curl -X POST "https://your-domain.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "your-password",
    "token_name": "API Integration"
  }'

# 2. Use token for authenticated requests
curl -X GET "https://your-domain.com/api/v1/devices" \
  -H "Authorization: Bearer {your-token-here}" \
  -H "Accept: application/json"

# 3. Create a device
curl -X POST "https://your-domain.com/api/v1/devices" \
  -H "Authorization: Bearer {your-token-here}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Server",
    "type": "server",
    "location": "Data Center A"
  }'

# 4. Logout (revoke current token)
curl -X POST "https://your-domain.com/api/v1/auth/logout" \
  -H "Authorization: Bearer {your-token-here}"
```

### Bulk IP Address Creation

```bash
# Create IP addresses from CIDR range
curl -X POST "https://your-domain.com/api/v1/ip-addresses/bulk-create" \
  -H "Authorization: Bearer {your-token-here}" \
  -H "Content-Type: application/json" \
  -d '{
    "cidr": "192.168.1.0/24",
    "group_id": 1,
    "type": "static",
    "status": "available",
    "description": "Production network range",
    "gateway": "192.168.1.1",
    "dns_primary": "8.8.8.8",
    "dns_secondary": "8.8.4.4"
  }'
```

### Advanced Filtering and Pagination

```bash
# Get active devices with pagination and filtering
curl -X GET "https://your-domain.com/api/v1/devices?status=active&type=server&search=web&page=1&per_page=10&sort_by=name&sort_direction=asc" \
  -H "Authorization: Bearer {your-token-here}" \
  -H "Accept: application/json"
```

## Support

For API support or questions, please contact the development team or refer to the project documentation.

---

**API Version:** v1  
**Last Updated:** December 2024