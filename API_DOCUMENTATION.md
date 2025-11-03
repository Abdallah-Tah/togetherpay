# TogetherPay API Documentation

## Overview

The TogetherPay API is a RESTful API that enables group bill management with recurring payment requests. This documentation covers all public API endpoints.

**Base URL:** `https://api.togetherpay.app/api/v1`

**Authentication:** Bearer token (Laravel Sanctum)

**Content Type:** `application/json`

## Authentication

### Register

Create a new user account.

**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "phone": "+1234567890"
}
```

**Response:** `201 Created`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "created_at": "2025-01-01T00:00:00.000000Z"
  },
  "token": "1|abc123...xyz789"
}
```

### Login

Authenticate and receive an access token.

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePassword123!"
}
```

**Response:** `200 OK`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "2|def456...uvw012"
}
```

### Logout

Revoke the current access token.

**Endpoint:** `POST /auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `204 No Content`

### Get Current User

Get authenticated user information.

**Endpoint:** `GET /auth/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

## Groups

### List Groups

Get all groups where the user is owner or member.

**Endpoint:** `GET /groups`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "name": "Family Netflix",
      "billing_day": 1,
      "cadence": "monthly",
      "amount": 15.99,
      "owner_id": 1,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "members_count": 4
    }
  ],
  "links": {},
  "meta": {}
}
```

### Create Group

Create a new bill-splitting group.

**Endpoint:** `POST /groups`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Family Netflix",
  "billing_day": 1,
  "cadence": "monthly",
  "amount": 15.99,
  "description": "Shared Netflix Premium subscription"
}
```

**Response:** `201 Created`
```json
{
  "id": 1,
  "name": "Family Netflix",
  "billing_day": 1,
  "cadence": "monthly",
  "amount": 15.99,
  "description": "Shared Netflix Premium subscription",
  "owner_id": 1,
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### Get Group

Get details of a specific group.

**Endpoint:** `GET /groups/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "name": "Family Netflix",
  "billing_day": 1,
  "cadence": "monthly",
  "amount": 15.99,
  "description": "Shared Netflix Premium subscription",
  "owner": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "members": [
    {
      "id": 1,
      "name": "Jane Doe",
      "phone": "+1234567890",
      "share_percentage": 25.00,
      "payment_channel": "venmo",
      "payment_handle": "@janedoe"
    }
  ],
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### Update Group

Update group details.

**Endpoint:** `PUT /groups/{id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Family Netflix Premium",
  "amount": 19.99,
  "description": "Updated to Premium plan"
}
```

**Response:** `200 OK`

### Delete Group

Delete a group (owner only).

**Endpoint:** `DELETE /groups/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `204 No Content`

## Group Members

### Add Member

Add a member to a group.

**Endpoint:** `POST /groups/{group_id}/members`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jane Doe",
  "phone": "+1234567890",
  "email": "jane@example.com",
  "share_percentage": 25.00,
  "payment_channel": "venmo",
  "payment_handle": "@janedoe"
}
```

**Response:** `201 Created`
```json
{
  "id": 1,
  "name": "Jane Doe",
  "phone": "+1234567890",
  "email": "jane@example.com",
  "share_percentage": 25.00,
  "payment_channel": "venmo",
  "payment_handle": "@janedoe",
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### Update Member

Update member details.

**Endpoint:** `PUT /groups/{group_id}/members/{member_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "share_percentage": 30.00,
  "payment_handle": "@janedoe-updated"
}
```

**Response:** `200 OK`

### Remove Member

Remove a member from a group.

**Endpoint:** `DELETE /groups/{group_id}/members/{member_id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `204 No Content`

## Payment Requests

### List Payment Requests

Get all payment requests for the authenticated user.

**Endpoint:** `GET /payment-requests`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `sent`, `paid`, `failed`)
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "pr_code": "PR-ABC123",
      "amount": 3.99,
      "status": "pending",
      "payment_channel": "venmo",
      "member": {
        "name": "Jane Doe",
        "phone": "+1234567890"
      },
      "group": {
        "name": "Family Netflix"
      },
      "due_date": "2025-01-15T00:00:00.000000Z",
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Get Payment Request

Get details of a specific payment request.

**Endpoint:** `GET /payment-requests/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "pr_code": "PR-ABC123",
  "amount": 3.99,
  "status": "pending",
  "payment_channel": "venmo",
  "payment_link": "venmo://paycharge?txn=charge&amount=3.99&note=...",
  "member": {
    "name": "Jane Doe",
    "phone": "+1234567890",
    "payment_handle": "@janedoe"
  },
  "group": {
    "name": "Family Netflix",
    "billing_day": 1
  },
  "cycle": {
    "start_date": "2025-01-01",
    "end_date": "2025-01-31"
  },
  "due_date": "2025-01-15T00:00:00.000000Z",
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### Create Manual Payment Request

Create a one-time payment request for a member.

**Endpoint:** `POST /groups/{group_id}/members/{member_id}/payment-requests`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": 5.00,
  "note": "Additional charge for extra user",
  "due_date": "2025-01-20"
}
```

**Response:** `201 Created`

### Mark Payment as Sent (Public - No Auth)

Member marks payment as sent using PR code.

**Endpoint:** `POST /requests/by-code/{pr_code}/mark`

**Request Body:**
```json
{
  "payment_method": "venmo",
  "transaction_id": "VEN-123456789",
  "note": "Paid via Venmo"
}
```

**Response:** `200 OK`
```json
{
  "status": "paid",
  "message": "Payment marked as sent successfully"
}
```

## Device Registration

### Register Device

Register a device for push notifications.

**Endpoint:** `POST /devices/register`

**Headers:**
```
Authorization: Bearer {token} (optional - can be guest)
Content-Type: application/json
```

**Request Body:**
```json
{
  "fcm_token": "fcm-token-here",
  "platform": "android",
  "device_id": "unique-device-id"
}
```

**Response:** `200 OK`
```json
{
  "message": "Device registered successfully"
}
```

## Cash App OAuth

### Initiate OAuth Flow

Get Cash App authorization URL.

**Endpoint:** `GET /cashapp/oauth/authorize`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "authorization_url": "https://cash.app/oauth/authorize?client_id=...&scope=PAYMENTS"
}
```

### OAuth Callback

Handle OAuth callback (called by Cash App).

**Endpoint:** `GET /cashapp/oauth/callback`

**Query Parameters:**
- `code`: Authorization code from Cash App
- `state`: State parameter for CSRF protection

**Response:** Redirect to mobile app with token

## Webhooks

### Cash App Webhook

Receive payment status updates from Cash App.

**Endpoint:** `POST /webhooks/cashapp`

**Headers:**
```
X-CashApp-Signature: {signature}
Content-Type: application/json
```

**Request Body:**
```json
{
  "event_type": "payment.completed",
  "payment_id": "PAY-123456",
  "amount": {
    "amount": 399,
    "currency": "USD"
  },
  "status": "COMPLETED"
}
```

**Response:** `200 OK`

## Error Responses

### Standard Error Format

```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200 OK` - Request succeeded
- `201 Created` - Resource created successfully
- `204 No Content` - Request succeeded with no response body
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Rate Limiting

**Authentication Endpoints:**
- 5 requests per minute

**API Endpoints:**
- 60 requests per minute

**Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

When rate limit is exceeded:
```json
{
  "message": "Too many requests. Please try again later."
}
```

## Pagination

List endpoints support pagination:

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

**Response:**
```json
{
  "data": [...],
  "links": {
    "first": "https://api.togetherpay.app/api/v1/groups?page=1",
    "last": "https://api.togetherpay.app/api/v1/groups?page=5",
    "prev": null,
    "next": "https://api.togetherpay.app/api/v1/groups?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

## Filtering & Sorting

### Payment Requests Filtering

```
GET /payment-requests?status=pending&sort=-created_at
```

**Available Filters:**
- `status`: `pending`, `sent`, `paid`, `failed`
- `payment_channel`: `venmo`, `cashapp`

**Available Sorts:**
- `created_at`, `-created_at` (descending)
- `due_date`, `-due_date`
- `amount`, `-amount`

## Idempotency

For critical operations (payments, webhook processing), use idempotency keys:

**Headers:**
```
Idempotency-Key: unique-key-123
```

Duplicate requests with the same key will return the original response.

## Versioning

The API uses URL-based versioning: `/api/v1/`

Breaking changes will be introduced in new versions (`/api/v2/`).

## SDKs & Libraries

### Mobile (Flutter)
```dart
import 'package:togetherpay_api/togetherpay_api.dart';

final client = TogetherPayClient(
  baseUrl: 'https://api.togetherpay.app/api/v1',
  token: 'your-auth-token',
);

// Login
final response = await client.auth.login(
  email: 'john@example.com',
  password: 'password',
);
```

## Support

**Documentation:** https://docs.togetherpay.app

**API Status:** https://status.togetherpay.app

**Support Email:** support@togetherpay.app

**Developer Portal:** https://developers.togetherpay.app
