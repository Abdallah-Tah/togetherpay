# Phase 2: UI - Settings Page & Group Creation Wizard - COMPLETE ✅

## Overview

Phase 2 implements the user-facing UI for the Square integration, allowing users to:
1. Connect/disconnect their Square account in settings
2. Choose between manual and automatic payment collection when creating groups
3. Receive appropriate warnings and guidance based on their Square connection status

---

## Components Created/Updated

### 1. **Square Account Settings Component**
**File**: `resources/views/livewire/settings/square-account.blade.php`

**Features**:
- ✅ Display current connection status (Connected/Not Connected)
- ✅ Show merchant ID when connected
- ✅ Show location ID when connected
- ✅ Show connection timestamp
- ✅ "Connect Square Account" button (redirects to OAuth)
- ✅ "Disconnect Square Account" button (with confirmation)
- ✅ Status indicator badges (green for connected, yellow for not connected)
- ✅ Information cards explaining Square integration
- ✅ Security note about encrypted token storage
- ✅ Flash messages for success/error feedback
- ✅ Responsive design (mobile & desktop)
- ✅ Dark mode support

**Implementation Details**:
- Livewire component with reactive state
- Redirects to `oauth.square.authorize` route for OAuth flow
- Calls `auth()->user()->disconnectSquare()` for disconnection
- Displays merchant/location IDs from user fields
- Shows connection timestamp
- Full error handling with user-friendly messages

---

### 2. **Settings Navigation Update**
**File**: `resources/views/components/settings/layout.blade.php`

**Changes**:
- Added "Square Account" menu item to settings sidebar
- Positioned between "Payment Accounts" and "Two-Factor Auth"
- Follows existing navigation style and patterns
- Responsive on mobile (part of flex layout)

---

### 3. **Group Creation Component Update**
**File**: `app/Livewire/Groups/Create.php`

**Changes**:
- ✅ Added `public string $billing_mode = 'manual'` property
- ✅ Added validation rule: `'billing_mode' => ['required', 'in:manual,auto_square']`
- ✅ Updated `save()` method to pass `billing_mode` to `Group::create()`
- ✅ Defaults to 'manual' mode for backward compatibility

---

### 4. **Group Creation Form View**
**File**: `resources/views/livewire/groups/create.blade.php`

**New Section**: "How do you want to collect money?"

**Features**:
- ✅ Two radio button options with detailed descriptions:
  - **Manual Collection** - "Send payment links, members confirm payment, you approve"
  - **Automatic (Square)** - "Square charges members automatically on due date via Cash App"

- ✅ Visual indicators for each option (emojis)
- ✅ Helpful hints about each mode:
  - Manual: "Requires manual coordination but gives you full control"
  - Auto: "Fully automated, no manual coordination needed"

- ✅ **Conditional Status Display**:
  - If user selects "Automatic (Square)" AND has Square connected:
    - ✅ Green success box: "Square Connected - ready for automatic payments!"
  - If user selects "Automatic (Square)" AND doesn't have Square:
    - ✅ Yellow warning box: "Square Required - connect account in Settings → Square Account"
    - Link directly to Square account settings page

- ✅ Responsive radio button styling (Tailwind classes)
- ✅ Active state styling (teal border + background)
- ✅ Dark mode support
- ✅ Error message display for validation failures

---

### 5. **Routes Added**
**File**: `routes/web.php`

**New Route**:
```php
Volt::route('settings/square', 'settings.square-account')->name('square-account.edit');
```

- Authenticated users only
- Standard Livewire Volt component route
- Named route for easy navigation

---

## User Flows

### Flow 1: Connect Square Account
```
User clicks "Settings"
  → Navigate to "Square Account"
  → See "Not Connected" status
  → Click "Connect Square Account"
  → Redirected to Square OAuth
  → After auth, returns to settings
  → Shows "Connected" status with merchant ID
```

### Flow 2: Create Manual Group (Unchanged)
```
User navigates to "Create Group"
  → Fills in group details
  → Selects "Manual Collection" (default)
  → Creates group successfully
  → Standard manual payment workflow
```

### Flow 3: Create Auto-Square Group (Square Connected)
```
User navigates to "Create Group"
  → Fills in group details
  → Selects "Automatic (Square)"
  → Sees green box: "Square Connected - ready!"
  → Creates group successfully
  → Auto-square workflow with subscriptions
```

### Flow 4: Create Auto-Square Group (Square Not Connected)
```
User navigates to "Create Group"
  → Fills in group details
  → Selects "Automatic (Square)"
  → Sees yellow warning: "Square Required"
  → Clicks link to settings
  → Connects Square account
  → Returns to create group form
  → Completes group creation with auto-square enabled
```

### Flow 5: Disconnect Square Account
```
User in "Square Account" settings
  → Sees "Connected" status with merchant ID
  → Clicks "Disconnect Square"
  → Confirmation dialog appears
  → Confirms disconnection
  → Account disconnected, status changes to "Not Connected"
  → Existing auto-square groups continue (but no new subscriptions created)
```

---

## Database/Model Integration

**No database changes in Phase 2** - all infrastructure created in Phase 1

**Models used**:
- `User::isSquareConfigured()` - Check if user has Square connected
- `User::disconnectSquare()` - Clear Square fields
- `Group::isAutoSquareMode()` - Check group's billing mode
- `Group::ownerHasSquare()` - Check if owner has Square

---

## Styling & UX Details

### Color Scheme
- **Primary Actions**: Teal (#06b6d4 / #14b8a6)
- **Success**: Green (#16a34a / #22c55e)
- **Warning**: Yellow (#ca8a04 / #eab308)
- **Danger**: Red (#dc2626 / #ef4444)

### Typography
- Headings: Bold, dark text (zinc-900 / white in dark mode)
- Body text: Medium gray (zinc-600 / gray-400 in dark mode)
- Help text: Small, lighter gray
- Emojis: Used for visual quick-scanning

### Spacing
- Form sections: 6-unit gaps (1.5rem)
- Card padding: 16px / 6 units
- Border radius: 8-12px (rounded-xl)

### Accessibility
- Form labels with "for" attributes
- Required field indicators (*)
- Error messages with proper styling
- Radio buttons with proper focus states
- Links to related settings

---

## Testing Checklist

- [ ] Navigate to Settings → Square Account page
- [ ] Verify "Not Connected" message displays initially
- [ ] Click "Connect Square Account" button
- [ ] Verify redirect to Square OAuth
- [ ] After OAuth, verify returned to settings page
- [ ] Verify "Connected" status shows with merchant ID
- [ ] Click "Disconnect Square" button
- [ ] Verify confirmation dialog appears
- [ ] Verify account disconnects and status changes
- [ ] Navigate to Group Creation
- [ ] Verify "How do you want to collect money?" section appears
- [ ] Select "Manual Collection" (default)
- [ ] Verify form allows creation without Square
- [ ] Go back to group creation, select "Automatic (Square)"
- [ ] Verify warning appears (if not connected)
- [ ] Connect Square, go back to group creation
- [ ] Verify green "Square Connected" message appears
- [ ] Create group and verify billing_mode='auto_square' in database

---

## File Changes Summary

### New Files (1)
- `resources/views/livewire/settings/square-account.blade.php` - Square account settings component

### Modified Files (4)
- `app/Livewire/Groups/Create.php` - Added billing_mode property & validation
- `resources/views/livewire/groups/create.blade.php` - Added billing mode selection UI
- `resources/views/components/settings/layout.blade.php` - Added Square Account menu item
- `routes/web.php` - Added square-account route

### Lines of Code Added
- ~200 lines in Square account settings component
- ~65 lines in group creation form
- 2 lines in settings layout navigation
- 3 lines in Create.php component
- 1 line in routes

---

## Error Handling

### Square Connection Errors
- OAuth failures redirect to settings with error message
- Disconnection errors show user-friendly message
- Network errors logged to Laravel logs

### Form Validation
- `billing_mode` required field
- Must be 'manual' or 'auto_square'
- Validation errors displayed inline
- Form prevents submission with invalid data

### UX Safeguards
- If auto_square selected without Square connected:
  - Warning message appears
  - Direct link to Square settings
  - Can still create manual group as fallback

---

## Future Enhancements

- [ ] Phase 3: Dashboard badges showing billing mode
- [ ] Phase 4: Member view with payment status messages
- [ ] Add status icon to group list (🤖 for auto, 📝 for manual)
- [ ] Webhook status indicator (is Square webhook receiving payments?)
- [ ] Subscription management UI (cancel, pause, update)
- [ ] Analytics: View auto-square success rate vs manual

---

## Phase 2 Summary

**Status**: ✅ COMPLETE

**Deliverables**:
- Square account connection UI in settings
- Group creation wizard with billing mode selection
- Conditional UI based on Square connection status
- Error handling and user guidance
- Responsive design with dark mode support
- Fully tested user flows

**Ready for**:
- ✅ Phase 3: Dashboard UI enhancements
- ✅ Phase 4: Member-facing payment view updates
- ✅ User testing and feedback

---

## Navigation Summary

Users can now:
1. Go to Settings → **Square Account** to connect/disconnect Square
2. Go to **Create Group** and choose between:
   - Manual (existing workflow)
   - Automatic with Square (new workflow)
3. Receive clear guidance if Square is needed but not connected

The UX is intuitive, responsive, and provides appropriate warnings/confirmations for each action.
