# API Implementation TODO List

This document tracks API endpoints that may need to be implemented or verified for the mobile app.

## ✅ Already Implemented

### Authentication
- ✅ `POST /api/v1/auth/register` - User registration
- ✅ `POST /api/v1/auth/login` - User login
- ✅ `POST /api/v1/auth/logout` - User logout
- ✅ `GET /api/v1/auth/me` - Get current user profile

### Groups
- ✅ `GET /api/v1/groups` - List user's groups
- ✅ `POST /api/v1/groups` - Create new group
- ✅ `GET /api/v1/groups/{id}` - Get group details
- ✅ `PUT /api/v1/groups/{id}` - Update group
- ✅ `DELETE /api/v1/groups/{id}` - Delete group
- ✅ `POST /api/v1/groups/{group}/members` - Add member to group
- ✅ `PUT /api/v1/groups/{group}/members/{member}` - Update member
- ✅ `DELETE /api/v1/groups/{group}/members/{member}` - Remove member from group

### Payment Requests
- ✅ `GET /api/v1/payment-requests` - List all payment requests
- ✅ `GET /api/v1/payment-requests/stats` - Get payment request statistics
- ✅ `GET /api/v1/payment-requests/{id}` - Get payment request details
- ✅ `POST /api/v1/payment-requests/{id}/mark-paid` - Mark payment as paid
- ✅ `POST /api/v1/payment-requests/{id}/retry` - Retry payment request
- ✅ `GET /api/v1/cycles/{cycle}/payment-requests` - Get payment requests for cycle
- ✅ `GET /api/v1/requests/by-code/{prCode}` - Get payment request by code (protected)
- ✅ `POST /api/v1/requests/by-code/{prCode}/mark` - Member marks payment as sent (public)
- ✅ `POST /api/v1/requests/by-code/{prCode}/confirm` - Confirm payment received
- ✅ `POST /api/v1/requests/by-code/{prCode}/nudge` - Send reminder
- ✅ `POST /api/v1/requests/by-code/{prCode}/cancel` - Cancel payment request
- ✅ `POST /api/v1/cycles/{cycleId}/bulk-confirm` - Bulk confirm payments
- ✅ `POST /api/v1/groups/{group}/members/{member}/payment-requests` - Create manual payment request

### Device Registration (Currently Not Used)
- ✅ `POST /api/v1/devices/register` - Register device for push notifications
  - **Note**: This endpoint exists but is not used by mobile app since Firebase was removed

### Cash App Integration
- ✅ `GET /api/v1/cashapp/oauth/authorize` - Initiate Cash App OAuth
- ✅ `GET /api/v1/cashapp/oauth/callback` - Handle Cash App OAuth callback
- ✅ `POST /api/v1/cashapp/resolve-cashtag` - Resolve Cash App cashtag
- ✅ `DELETE /api/v1/cashapp/disconnect` - Disconnect Cash App account
- ✅ `GET /api/v1/cashapp/status` - Get Cash App connection status

## ⚠️ Needs Verification

### Payment Requests Screen
The mobile app currently has a placeholder for the payment requests screen. To implement it, verify these endpoints work correctly:
- ✅ `GET /api/v1/payment-requests` - Should return all payment requests for the authenticated user
- ✅ `GET /api/v1/payment-requests/stats` - Should return statistics (overdue, pending, paid counts)

**Implementation needed in mobile app**: Create `lib/screens/payments/payment_requests_screen.dart`

## 🔧 API Route Fix Required

As mentioned in the mobile app documentation (`README_API_INTEGRATION.md`):

### Public Access for Payment Request Details
- **Issue**: `GET /api/v1/requests/by-code/{prCode}` is currently protected (requires authentication)
- **Required**: This endpoint should be **public** (no authentication required)
- **Reason**: Members receive payment links via notifications/email and need to view payment details without logging in
- **Security**: The PR code acts as authentication (like a magic link)

**Fix in `routes/api.php`**:
```php
// Move this line from protected section to public section
Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
```

## 📋 Optional Enhancements

These are not required but could improve the mobile app experience:

### User Profile
- 💡 `PUT /api/v1/auth/me` - Update user profile (name, email)
- 💡 `PUT /api/v1/auth/password` - Change password
- 💡 `POST /api/v1/auth/forgot-password` - Request password reset
- 💡 `POST /api/v1/auth/reset-password` - Reset password with token

### Notifications Preferences
- 💡 `GET /api/v1/notifications/preferences` - Get notification preferences
- 💡 `PUT /api/v1/notifications/preferences` - Update notification preferences
- 💡 `GET /api/v1/notifications` - Get notification history
- 💡 `POST /api/v1/notifications/{id}/mark-read` - Mark notification as read

### Payment Methods Management
- 💡 `GET /api/v1/payment-methods` - List saved payment methods
- 💡 `POST /api/v1/payment-methods` - Add payment method
- 💡 `PUT /api/v1/payment-methods/{id}` - Update payment method
- 💡 `DELETE /api/v1/payment-methods/{id}` - Remove payment method
- 💡 `POST /api/v1/payment-methods/{id}/verify` - Verify payment method

### Group Activity
- 💡 `GET /api/v1/groups/{group}/activity` - Get group activity feed
- 💡 `GET /api/v1/groups/{group}/stats` - Get group statistics

### Venmo Integration
- 💡 `GET /api/v1/venmo/oauth/authorize` - Initiate Venmo OAuth
- 💡 `GET /api/v1/venmo/oauth/callback` - Handle Venmo OAuth callback
- 💡 `POST /api/v1/venmo/resolve-username` - Resolve Venmo username
- 💡 `DELETE /api/v1/venmo/disconnect` - Disconnect Venmo account
- 💡 `GET /api/v1/venmo/status` - Get Venmo connection status

## 🚀 Future Considerations

### Push Notifications (If Re-implemented)
If push notifications are added back to the mobile app, consider:
- 💡 Update device registration to support multiple tokens per user
- 💡 Add endpoint to list user's registered devices
- 💡 Add endpoint to remove/unregister device
- 💡 Add notification preferences per device

### Analytics & Reporting
- 💡 `GET /api/v1/reports/groups` - Group reports
- 💡 `GET /api/v1/reports/members` - Member reports
- 💡 `GET /api/v1/exports/group/{group}` - Export group data (CSV/PDF)

## Testing Checklist

Before marking the mobile app as complete:

- [ ] Test all authentication flows (register, login, logout)
- [ ] Test group CRUD operations
- [ ] Test adding/removing members from groups
- [ ] Test payment request listing and filtering
- [ ] Test marking payments as sent/paid
- [ ] Test Cash App deep link integration
- [ ] Test Venmo deep link integration
- [ ] Verify error handling for all API calls
- [ ] Test offline behavior
- [ ] Test token expiration and refresh

## Notes

1. **Device Registration**: The `/api/v1/devices/register` endpoint is implemented but not used since Firebase was removed. Consider:
   - Removing the endpoint if push notifications won't be re-implemented
   - Keeping it if alternative push notification solution will be used
   - Documenting its purpose if kept for future use

2. **Payment Methods Screen**: The mobile app has a placeholder for `PaymentMethodsScreen` but doesn't use any payment methods API yet. Implement when needed.

3. **Notification Preferences Screen**: The mobile app has a placeholder for `NotificationPreferencesScreen` but doesn't use any notification preferences API yet. Implement when needed.

4. **API Versioning**: All endpoints are under `/api/v1` prefix. Consider versioning strategy for future API changes.

5. **Rate Limiting**: Consider implementing rate limiting for sensitive endpoints (auth, payment operations).

6. **Pagination**: Consider adding pagination support to list endpoints for better performance with large datasets.
