# OpenIPAM API Documentation & Test Coverage

## Table of Contents
- [API Overview](#api-overview)
- [Authentication](#authentication)
- [Base URL & Headers](#base-url--headers)
- [Rate Limiting](#rate-limiting)
- [Error Handling](#error-handling)
- [Authentication Endpoints](#authentication-endpoints)
- [API Token Management](#api-token-management)
- [User Management](#user-management)
- [Device Management](#device-management)
- [IP Address Management](#ip-address-management)
- [IP Address Groups](#ip-address-groups)
- [Pagination](#pagination)
- [Filtering & Search](#filtering--search)
- [Test Coverage Status](#test-coverage-status)

## API Overview

OpenIPAM provides a comprehensive RESTful API for programmatic access to all IPAM functionality:

- **94 API tests passing** (100% success rate)
- **Full CRUD operations** for all resources
- **Token-based authentication** with Laravel Sanctum
- **Bulk operations** for IP address management
- **Advanced filtering and search** capabilities
- **Pagination support** for large datasets
- **Rate limiting** and security features

## Authentication

The OpenIPAM API uses token-based authentication. There are two ways to authenticate:

1. **Session-based authentication** (for web applications)
2. **API token authentication** (recommended for external integrations)

### Getting Started

First, you need to authenticate and obtain an API token:

```bash
# Login and get session token
curl -X POST http://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "your-password"
  }'
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "language": "en"
  },
  "token": "1|abc123def456...",
  "expires_at": "2025-01-17T10:30:00.000000Z"
}
```

### Using API Tokens

Once you have a token, include it in the `Authorization` header:

```bash
curl -H "Authorization: Bearer 1|abc123def456..." \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/users
```

## Base URL & Headers

- **Base URL:** `http://your-domain.com/api/v1`
- **Required Headers:**
  ```
  Accept: application/json
  Content-Type: application/json (for POST/PUT requests)
  Authorization: Bearer YOUR_TOKEN
  ```

## Rate Limiting

The API implements rate limiting:
- **60 requests per minute** per user
- Rate limit headers are included in responses:
  ```
  X-RateLimit-Limit: 60
  X-RateLimit-Remaining: 59
  X-RateLimit-Reset: 1640995200
  ```

## Error Handling

The API returns standard HTTP status codes and JSON error responses:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Common Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Rate Limit Exceeded
- `500` - Server Error

## Authentication Endpoints

### Login
```bash
curl -X POST http://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password",
    "remember": true,
    "expires_in": 43200
  }'
```

### Register
```bash
curl -X POST http://your-domain.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Get Profile
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/auth/me
```

### Logout
```bash
curl -X POST http://your-domain.com/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## API Token Management

### List Tokens
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/tokens
```

### Create Token
```bash
curl -X POST http://your-domain.com/api/v1/tokens \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "My API Token",
    "expires_at": "2025-12-31T23:59:59Z",
    "abilities": ["*"]
  }'
```

### Revoke Token
```bash
curl -X DELETE http://your-domain.com/api/v1/tokens/TOKEN_ID \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## User Management

### List Users
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/users?page=1&per_page=15"
```

### Get User
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/users/1
```

### Create User
```bash
curl -X POST http://your-domain.com/api/v1/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password123",
    "language": "en",
    "email_two_factor_enabled": false
  }'
```

### Update User
```bash
curl -X PUT http://your-domain.com/api/v1/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Smith",
    "language": "de"
  }'
```

### Delete User
```bash
curl -X DELETE http://your-domain.com/api/v1/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Filter Users
```bash
# Filter by two-factor authentication status
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/users?email_two_factor_enabled=true"

# Search users
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/users?search=admin"

# Filter by language
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/users?language=en"
```

## Device Management

### List Devices
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?page=1&per_page=15"
```

### Get Device
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/devices/1
```

### Create Device
```bash
curl -X POST http://your-domain.com/api/v1/devices \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Server-01",
    "type": "server",
    "mac_address": "00:11:22:33:44:55",
    "description": "Production web server",
    "location": "Data Center A",
    "url": "https://server01.example.com"
  }'
```

### Update Device
```bash
curl -X PUT http://your-domain.com/api/v1/devices/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Server-01-Updated",
    "location": "Data Center B"
  }'
```

### Delete Device
```bash
curl -X DELETE http://your-domain.com/api/v1/devices/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Assign IP to Device
```bash
curl -X POST http://your-domain.com/api/v1/devices/1/assign-ip \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ip_address_id": 5
  }'
```

### Unassign IP from Device
```bash
curl -X POST http://your-domain.com/api/v1/devices/1/unassign-ip \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ip_address_id": 5
  }'
```

### Filter Devices
```bash
# Filter by type
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?type=server"

# Search devices
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?search=web"

# Sort devices
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?sort=name&direction=asc"
```

## IP Address Management

### List IP Addresses
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/ip-addresses?page=1&per_page=15"
```

### Get IP Address
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/ip-addresses/1
```

### Create IP Address
```bash
curl -X POST http://your-domain.com/api/v1/ip-addresses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ip_address": "192.168.1.100",
    "subnet_mask": "255.255.255.0",
    "gateway": "192.168.1.1",
    "dns_servers": ["8.8.8.8", "8.8.4.4"],
    "status": "available",
    "description": "Static IP for server",
    "ip_address_group_id": 1
  }'
```

### Update IP Address
```bash
curl -X PUT http://your-domain.com/api/v1/ip-addresses/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "assigned",
    "description": "Assigned to web server"
  }'
```

### Delete IP Address
```bash
curl -X DELETE http://your-domain.com/api/v1/ip-addresses/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Bulk Create IP Addresses (CIDR)
```bash
curl -X POST http://your-domain.com/api/v1/ip-addresses/bulk-create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "cidr": "192.168.1.0/24",
    "gateway": "192.168.1.1",
    "dns_servers": ["8.8.8.8", "8.8.4.4"],
    "status": "available",
    "ip_address_group_id": 1,
    "exclude_network": true,
    "exclude_broadcast": true,
    "exclude_ips": ["192.168.1.1", "192.168.1.254"]
  }'
```

### Bulk Update IP Addresses
```bash
curl -X PUT http://your-domain.com/api/v1/ip-addresses/bulk-update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ip_address_ids": [1, 2, 3],
    "status": "reserved",
    "description": "Reserved for infrastructure"
  }'
```

### Filter IP Addresses
```bash
# Filter by status
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/ip-addresses?status=available"

# Filter by group
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/ip-addresses?ip_address_group_id=1"

# Search IP addresses
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/ip-addresses?search=192.168"

# Filter available IPs only
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/ip-addresses?available_only=true"
```

## IP Address Groups

### List IP Address Groups
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/ip-address-groups?page=1&per_page=15"
```

### Get IP Address Group
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  http://your-domain.com/api/v1/ip-address-groups/1
```

### Create IP Address Group
```bash
curl -X POST http://your-domain.com/api/v1/ip-address-groups \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Production Network",
    "description": "Main production network range",
    "cidr": "192.168.1.0/24",
    "default_gateway": "192.168.1.1",
    "default_dns_servers": ["8.8.8.8", "8.8.4.4"]
  }'
```

### Update IP Address Group
```bash
curl -X PUT http://your-domain.com/api/v1/ip-address-groups/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "description": "Updated production network description"
  }'
```

### Delete IP Address Group
```bash
curl -X DELETE http://your-domain.com/api/v1/ip-address-groups/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Pagination

All list endpoints support pagination:

```bash
# Basic pagination
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?page=2&per_page=10"
```

**Response Format:**
```json
{
  "data": [...],
  "links": {
    "first": "http://your-domain.com/api/v1/devices?page=1",
    "last": "http://your-domain.com/api/v1/devices?page=5",
    "prev": "http://your-domain.com/api/v1/devices?page=1",
    "next": "http://your-domain.com/api/v1/devices?page=3"
  },
  "meta": {
    "current_page": 2,
    "from": 11,
    "last_page": 5,
    "per_page": 10,
    "to": 20,
    "total": 50
  }
}
```

## Filtering & Search

### Global Search
Most endpoints support search across multiple fields:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?search=server"
```

### Sorting
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?sort=name&direction=desc"
```

### Multiple Filters
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  "http://your-domain.com/api/v1/devices?type=server&search=web&sort=name&direction=asc&page=1&per_page=20"
```

## Test Coverage Status

### ✅ Fully Tested & Passing (94 Tests)

#### 1. **AuthControllerTest** (9 Tests) ✅
- User login with valid credentials
- Failed login with invalid credentials
- User registration with valid data
- Registration validation errors
- Authenticated user profile access
- Unauthenticated access prevention
- User logout functionality
- Login with custom token expiration
- Never-expiring token creation

#### 2. **ApiTokenControllerTest** (15 Tests) ✅
- List user API tokens
- Create new API tokens
- Custom expiration token creation
- Never-expiring token creation
- Custom abilities token creation
- Invalid token data validation
- Past expiration token rejection
- API token revocation/deletion
- Non-existent token handling
- Cross-user token security
- Default token expiration (1 year)
- Unauthorized access prevention
- Plain-text token response
- Token list security (no plain-text)
- Token expiry status display

#### 3. **UserControllerTest** (18 Tests) ✅
- Complete CRUD operations
- User search and filtering
- Two-factor authentication filtering
- Language preference filtering
- User deletion with token cleanup
- Password updates
- Validation error handling
- Authorization checks

#### 4. **DeviceControllerTest** (14 Tests) ✅
- Device CRUD operations
- Type filtering and search
- IP assignment/unassignment
- Device sorting and pagination
- Validation and authorization
- URL field management

#### 5. **IpAddressControllerTest** (16 Tests) ✅
- IP address CRUD operations
- Bulk CIDR creation with exclusions
- Bulk update operations
- Status filtering and search
- Available IP filtering
- Group assignment
- Network/broadcast exclusion logic

#### 6. **IpAddressGroupControllerTest** (14 Tests) ✅
- Group CRUD operations
- CIDR management
- IP count tracking
- Search and filtering
- Partial updates
- Authorization checks

#### 7. **ApiIntegrationTest** (6 Tests) ✅
- Complete device and IP workflow
- Authentication flow testing
- Error handling validation
- Pagination and filtering
- Rate limiting verification
- Response format validation

#### 8. **UnauthorizedAccessTest** (2 Tests) ✅
- Unauthorized route protection
- Invalid token handling

### Key Features Tested:
- **Authentication & Authorization:** Laravel Sanctum integration
- **CRUD Operations:** Full coverage for all resources  
- **Bulk Operations:** IP address creation from CIDR ranges
- **Filtering & Search:** Advanced querying capabilities
- **Pagination:** Large dataset handling
- **Validation:** Comprehensive input validation
- **Rate Limiting:** API protection mechanisms
- **Error Handling:** Consistent error responses
- **Security:** Token management and access control

### Running the Tests

```bash
# Run all API tests
php artisan test --filter="Api"

# Run specific test class
php artisan test tests/Feature/Api/V1/DeviceControllerTest.php

# Run with detailed output
php artisan test --filter="Api" --testdox

# Check test coverage
php artisan test --coverage
```

---

**Need help?** Check the main [README.md](README.md) for installation and setup instructions.