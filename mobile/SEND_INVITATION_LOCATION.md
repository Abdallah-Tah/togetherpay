# Send Invitation Feature - Mobile App

## ✅ Implementation Complete

The mobile app now has a **Send Invitation** feature that allows group owners to invite members via email!

---

## Where to Find It

### Location in App

1. **Open the app** and login as a group owner
2. **Navigate to a group** (tap any group from Groups tab)
3. **Tap the 3-dot menu (⋮)** in the top right corner
4. **Select "Send invitation"** from the menu

### Menu Options

The popup menu now has **three options**:

```
⋮ More Options
├── 📧 Send invitation        (NEW!)
│   └── Invite via email
├── 👤 Add member directly
│   └── No invitation sent
└── 🗑️ Delete group
```

---

## What It Does

### Send Invitation Flow

1. **Tap "Send invitation"** → Bottom sheet appears
2. **Fill in member details:**
   - ✅ Email (required)
   - ✅ Full name (required)
   - ✅ Phone number (optional)
   - ✅ Number of shares (required, default: 1)
   - ✅ Venmo username (optional)
   - ✅ Cash App tag (optional)
3. **Tap "Send Invitation"**
4. **Backend sends email** with invitation link
5. **Success message** appears with option to copy link
6. **Sheet closes** automatically

### What the Member Receives

The member gets an **email invitation** with:
- Group name and who invited them
- Clickable link to accept invitation
- Link opens the mobile app directly (deep link)
- Can create account or login to accept

---

## Files Created/Modified

### New Files

**[mobile/lib/screens/groups/send_invitation_sheet.dart](lib/screens/groups/send_invitation_sheet.dart)** (393 lines)
- Complete invitation form UI
- Form validation
- API integration with `/groups/{id}/invitations`
- Success/error handling
- Copy invitation link to clipboard

### Modified Files

**[mobile/lib/screens/groups/group_details_screen.dart](lib/screens/groups/group_details_screen.dart)**
- Added `SendInvitationSheet` import
- Added `_showSendInvitationSheet()` method (lines 517-531)
- Added "Send invitation" menu item (lines 640-651)
- Updated menu to distinguish invitation vs direct add

---

## Differences: Send Invitation vs Add Member

### Send Invitation (NEW)
```
✅ Sends email with invitation link
✅ Member clicks link to accept
✅ Member can create account via app
✅ Better UX for new users
✅ Member gets welcome email
✅ Uses invitation acceptance flow

API: POST /groups/{id}/invitations
```

### Add Member Directly (Existing)
```
❌ No email sent
❌ Member must already exist in system
❌ Requires app pre-installed
❌ No account creation flow
❌ Directly adds to group

API: POST /groups/{id}/members
```

**Recommendation:** Use **Send Invitation** for most cases!

---

## How It Works

### API Call

```dart
await dio.post(
  '/groups/${groupId}/invitations',
  data: {
    'email': 'member@example.com',
    'name': 'John Doe',
    'phone': '+1234567890',
    'share_count': 1,
    'venmo_username': 'johndoe',
    'cashapp_cashtag': 'johndoe',
  },
);
```

### Backend Response

```json
{
  "message": "Invitation sent successfully",
  "invitation": {
    "id": 123,
    "email": "member@example.com",
    "status": "pending",
    "expires_at": "2025-01-28T12:00:00Z",
    "invitation_url": "https://togetherpay.app/accept-invitation/abc123"
  }
}
```

### Email Sent to Member

```
Subject: You're invited to join [Group Name] on TogetherPay

Hi John Doe,

[Owner Name] has invited you to join [Group Name] on TogetherPay.

TogetherPay makes group bill splitting effortless with automatic
payment requests and reminders.

[Accept Invitation Button]

This invitation expires in 7 days.
```

---

## Form Fields Explained

### Required Fields

**Email**
- Used to send invitation
- Member's account email
- Validated for format

**Full Name**
- How member appears in group
- Pre-filled in invitation acceptance

**Number of Shares**
- Default: 1
- Determines payment split
- Example: If 3 members with 1 share each = split 3 ways

### Optional Fields

**Phone Number**
- For SMS notifications (future)
- Contact information

**Venmo Username**
- Pre-filled for member
- Makes payments easier
- Without @ symbol

**Cash App Tag**
- Pre-filled for member
- Alternative payment method
- Without $ symbol

---

## User Experience

### Owner Flow

```
Groups → Select Group → Tap ⋮ → Send invitation
→ Fill form → Send → ✅ "Invitation sent successfully"
→ Option to copy link → Sheet closes
```

### Member Flow

```
Email received → Click "Accept Invitation" link
→ App opens → Invitation details screen
→ Create account OR login → Accept
→ Added to group → See payment requests
```

---

## Testing

### Quick Test

1. **Build and install** latest app:
   ```bash
   flutter build apk --debug
   flutter install
   ```

2. **Login as group owner**

3. **Create test invitation:**
   - Go to any group
   - Tap ⋮ menu → "Send invitation"
   - Email: your-test@email.com
   - Name: Test User
   - Shares: 1
   - Tap "Send Invitation"

4. **Check email** for invitation

5. **Click link** in email → App opens with invitation screen

6. **Accept invitation** → User added to group

---

## Screenshots

### Send Invitation Button in Menu

```
┌─────────────────────────┐
│  Group Details    ⋮     │
├─────────────────────────┤
│  📧 Send invitation     │
│     Invite via email    │
├─────────────────────────┤
│  👤 Add member directly │
│     No invitation sent  │
├─────────────────────────┤
│  🗑️ Delete group        │
└─────────────────────────┘
```

### Invitation Form

```
┌─────────────────────────────┐
│  📧 Send Invitation      ✕  │
│  Member will receive email  │
│                             │
│  📧 Email *                 │
│  [member@example.com]       │
│                             │
│  👤 Full Name *             │
│  [John Doe]                 │
│                             │
│  📱 Phone (optional)        │
│  [+1234567890]              │
│                             │
│  📊 Number of Shares *      │
│  [1]                        │
│                             │
│  Payment Methods (Optional) │
│                             │
│  💰 Venmo Username          │
│  [johndoe]                  │
│                             │
│  💵 Cash App Tag            │
│  [johndoe]                  │
│                             │
│  [📤 Send Invitation]       │
│                             │
│  ℹ️ Member will receive     │
│     an email invitation     │
└─────────────────────────────┘
```

---

## Benefits

### For Owners

✅ **Easier onboarding** - Just need email
✅ **Professional** - Automated invitation emails
✅ **Trackable** - See invitation status
✅ **Flexible** - Member can accept anytime (7 days)
✅ **Copy link** - Share via text/WhatsApp too

### For Members

✅ **Simple** - Click link to join
✅ **Guided** - Step-by-step acceptance
✅ **Secure** - Token-based, expires in 7 days
✅ **Welcome email** - Confirmation after joining
✅ **Pre-filled** - Payment details already set

---

## Technical Details

### API Endpoint

```
POST /api/v1/groups/{groupId}/invitations
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "email": "string (required)",
  "name": "string (required)",
  "phone": "string (optional)",
  "share_count": "integer (required, min: 1)",
  "venmo_username": "string (optional)",
  "cashapp_cashtag": "string (optional)"
}

Response (201):
{
  "message": "Invitation sent successfully",
  "invitation": {
    "id": "integer",
    "email": "string",
    "status": "pending",
    "expires_at": "timestamp",
    "invitation_url": "string"
  }
}
```

### State Management

```dart
bool _loading = false;    // Form submission state
String? _error;           // Error message display
```

### Form Validation

- Email format check
- Name not empty
- Share count >= 1
- Phone optional
- Payment methods optional

---

## Future Enhancements

### Potential Improvements

1. **Invitation list** - View all pending invitations
2. **Resend** - Resend invitation if expired
3. **Cancel** - Cancel pending invitation
4. **Template** - Save common member details
5. **Bulk invite** - Invite multiple at once
6. **SMS option** - Send via SMS instead of email
7. **WhatsApp share** - Share link via WhatsApp
8. **QR code** - Generate QR for in-person invites

---

## Summary

✅ **Send Invitation feature is live!**
✅ **Located in:** Group Details → ⋮ menu → "Send invitation"
✅ **Sends email** with deep link to accept
✅ **Professional UI** with form validation
✅ **Copy link option** for sharing via other channels
✅ **Works with invitation acceptance flow**

**Try it now:**
```bash
flutter install
```

Then navigate to: **Groups → Select Group → ⋮ → Send invitation**

🎉 Members can now be invited professionally via email!

---

**Files:**
- 📄 [send_invitation_sheet.dart](lib/screens/groups/send_invitation_sheet.dart) - New invitation form
- 📄 [group_details_screen.dart](lib/screens/groups/group_details_screen.dart) - Updated menu
- 📄 [INVITATION_FEATURE.md](INVITATION_FEATURE.md) - Complete invitation docs
