# Group Invitation System

## Overview

The invitation system allows group owners to invite new members to join their groups via email. Members who accept invitations are automatically added to the group and can view/pay their payment requests, but **cannot see the group in their groups list**.

## Key Features

- **Email Invitations**: Owners send invitations via email with unique secure tokens
- **7-Day Expiration**: Invitations automatically expire after 7 days
- **Member Permissions**: Members can only view their own payment requests, not the groups list
- **Owner Permissions**: Owners can create groups, invite members, and manage all group settings

## Database Schema

### `group_invitations` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `group_id` | bigint | Foreign key to groups table |
| `invited_by_user_id` | bigint | User who sent the invitation |
| `email` | string | Email address of invitee |
| `phone` | string (nullable) | Phone number of invitee |
| `token` | string (unique) | Secure 32-character invitation token |
| `status` | enum | `pending`, `accepted`, `expired`, `cancelled` |
| `expires_at` | timestamp | When the invitation expires (7 days from creation) |
| `accepted_at` | timestamp (nullable) | When the invitation was accepted |
| `accepted_by_user_id` | bigint (nullable) | User who accepted the invitation |
| `member_data` | json | Stores intended member data (name, shares, payment handles) |
| `created_at` | timestamp | When invitation was created |
| `updated_at` | timestamp | When invitation was last updated |

## API Endpoints

### 1. Send Invitation (Owner Only)

```http
POST /api/v1/groups/{group}/invitations
Authorization: Bearer {token}
Content-Type: application/json

{
  "email": "member@example.com",
  "phone": "+1234567890",
  "name": "John Doe",
  "share_count": 1,
  "venmo_username": "@johndoe",
  "cashapp_cashtag": "$johndoe"
}
```

**Response:**
```json
{
  "message": "Invitation sent successfully",
  "invitation": {
    "id": 1,
    "email": "member@example.com",
    "status": "pending",
    "expires_at": "2025-11-15T21:04:00.000000Z",
    "invitation_url": "https://togetherpay.app/accept-invitation/abc123..."
  }
}
```

### 2. View Invitation Details (Public)

```http
GET /api/v1/invitations/{token}
```

**Response:**
```json
{
  "invitation": {
    "id": 1,
    "group": {
      "id": 123,
      "name": "Mother Medics"
    },
    "invited_by": {
      "name": "Alice Johnson"
    },
    "email": "member@example.com",
    "status": "pending",
    "expires_at": "2025-11-15T21:04:00.000000Z",
    "is_valid": true,
    "member_data": {
      "name": "John Doe",
      "share_count": 1,
      "venmo_username": "@johndoe",
      "cashapp_cashtag": "$johndoe"
    }
  }
}
```

### 3. Accept Invitation (Requires Auth)

```http
POST /api/v1/invitations/{token}/accept
Authorization: Bearer {token}
Content-Type: application/json

{
  "accept": true
}
```

**Response:**
```json
{
  "message": "Successfully joined the group!",
  "member": {
    "id": 42,
    "group_id": 123,
    "user_id": 5,
    "name": "John Doe",
    "share_count": 1,
    "venmo_username": "@johndoe",
    "cashapp_cashtag": "$johndoe"
  },
  "group": {
    "id": 123,
    "name": "Mother Medics"
  }
}
```

### 4. Cancel Invitation (Owner Only)

```http
DELETE /api/v1/groups/{group}/invitations/{invitation}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Invitation cancelled successfully"
}
```

## User Flow

### For Group Owners:

1. **Create Group**: Owner creates a new group
2. **Send Invitation**: Owner sends invitation to member's email
3. **Email Sent**: Member receives email with invitation link
4. **Manage Invitations**: Owner can view pending/accepted invitations
5. **Cancel if Needed**: Owner can cancel pending invitations

### For Members:

1. **Receive Email**: Member receives invitation email
2. **View Invitation**: Member clicks link to view invitation details (no login required)
3. **Register/Login**: If not registered, member creates account with matching email
4. **Accept Invitation**: Member accepts invitation (requires login)
5. **Auto-Added**: Member is automatically added to group
6. **View Payment Requests**: Member can now view/pay their payment requests
7. **Create Own Groups**: Member can also create their own groups and become an owner

## Member Permissions

### What Members CAN Do:
- ✅ View their own payment requests
- ✅ Mark their own payments as paid
- ✅ Receive push notifications for payment requests
- ✅ Pay via Venmo/Cash App deep links
- ✅ Create their own groups (become owner)
- ✅ View their payment history

### What Members CANNOT Do:
- ❌ See list of groups they're a member of
- ❌ View other members in the group
- ❌ Edit group settings
- ❌ Send payment requests to others
- ❌ View group financials/reports
- ❌ Invite other members

## Email Notification

Members receive an email invitation with:
- Group name and owner's name
- Invitation expiration date
- "Accept Invitation" button linking to the app
- Information about TogetherPay features
- Instructions for new users

Example email subject: **"You've been invited to join Mother Medics"**

## Security Features

1. **Unique Tokens**: 32-character random tokens prevent guessing
2. **Email Verification**: Accepting user's email must match invitation email
3. **Time-Limited**: Invitations expire after 7 days
4. **Owner Authorization**: Only group owners can send/cancel invitations
5. **Duplicate Prevention**: Cannot send multiple pending invitations to same email
6. **Member Validation**: Cannot invite existing group members

## Implementation Details

### GroupInvitation Model Methods

```php
// Generate unique token
GroupInvitation::generateToken(): string

// Check if still valid
$invitation->isValid(): bool

// Check if expired
$invitation->isExpired(): bool

// Mark as accepted
$invitation->markAsAccepted(User $user): void
```

### Query Scopes

```php
// Get pending non-expired invitations
GroupInvitation::pending()->get()

// Get invitations for specific email
GroupInvitation::forEmail('user@example.com')->get()
```

## Testing the Flow

### 1. Owner Sends Invitation

```bash
curl -X POST https://your-api.com/api/v1/groups/1/invitations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newmember@example.com",
    "name": "New Member",
    "share_count": 1
  }'
```

### 2. Check Invitation (No Auth)

```bash
curl https://your-api.com/api/v1/invitations/{TOKEN}
```

### 3. Accept Invitation (With Auth)

```bash
curl -X POST https://your-api.com/api/v1/invitations/{TOKEN}/accept \
  -H "Authorization: Bearer MEMBER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"accept": true}'
```

## Future Enhancements

- [ ] SMS invitations (in addition to email)
- [ ] Invitation reminders after 3 days
- [ ] Bulk invitation support
- [ ] Custom invitation messages
- [ ] Invitation analytics (view/accept rates)
- [ ] Deep link support for mobile app invitation flow

## Mobile App Integration

The mobile app should:
1. Handle deep links: `togetherpay://accept-invitation/{token}`
2. Show invitation details screen
3. Prompt user to register/login if not authenticated
4. Call accept endpoint after authentication
5. Navigate to payment requests screen after successful acceptance

## Notes

- Members only see payment requests, not groups - this is intentional for privacy
- Each member can create their own groups and become an owner
- Invitations are queued for async email sending
- Failed email notifications don't prevent invitation creation
- Expired invitations are automatically marked as expired when accessed
