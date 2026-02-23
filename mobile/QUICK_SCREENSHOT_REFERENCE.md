# 📸 Quick Screenshot Reference Card

Keep this open while capturing screenshots!

## 🚀 Quick Start

```bash
cd mobile
./scripts/launch_for_screenshots.sh
```

## 📱 Devices Needed

### iOS (Required)
- ✅ iPhone 15 Pro Max (6.7")
- ✅ iPhone 14 Plus (6.5")
- 📋 iPhone 8 Plus (5.5") - optional

### Android (Required)
- ✅ Pixel 9 Pro

## 🎬 The 6 Screens (IN ORDER)

| # | Screen | Navigation | Key Elements |
|---|--------|------------|--------------|
| 1 | **Onboarding** | Launch app (logged out) | Welcome message, branding |
| 2 | **Dashboard** | Home screen | 3-4 groups visible |
| 3 | **Group Details** | Tap a group | Member list, amounts, statuses |
| 4 | **Add Member** | Group → Add Member | ⚠️ MUST show Venmo/Cash App! |
| 5 | **Payment Requests** | Main menu → Requests | List with varied statuses |
| 6 | **Request Detail** | Tap a request | ⚠️ MUST show payment buttons! |

## ⚠️ CRITICAL: Screens #4 and #6

These screens MUST clearly show:
- **#4**: Payment channel selection (Venmo/Cash App dropdown/options)
- **#6**: Venmo and Cash App payment buttons

These prove to reviewers that the app functions as described!

## 🎨 Demo Data Checklist

Before starting, ensure you have:
- ✅ Test account logged in
- ✅ 3-4 groups created:
  - "Family Phone Plan"
  - "Netflix Premium"
  - "Apartment Utilities"
- ✅ Each group has 4-5 members
- ✅ Mix of payment statuses (Paid ✓, Pending ⏱)
- ✅ Multiple payment requests visible

## 📸 How to Capture

**iOS**: Press `⌘ + S` → Saves to Desktop
**Android**: Click camera icon in toolbar

## ✅ Quality Check

Each screenshot should have:
- [ ] No real personal data
- [ ] Time shows 9:41 AM
- [ ] Full battery, good signal
- [ ] No error messages
- [ ] All UI elements visible
- [ ] Portrait orientation
- [ ] Proper lighting/contrast

## 📂 Save Location

Move screenshots from Desktop to:

```
mobile/screenshots/
├── ios/6.7-inch/01-onboarding.png
├── ios/6.5-inch/01-onboarding.png
└── android/phone/01-onboarding.png
```

## 🔄 Workflow Per Device

1. Launch device using script
2. Navigate to screen #1
3. Press ⌘+S (iOS) or camera icon (Android)
4. Navigate to screen #2
5. Press ⌘+S
6. Repeat for screens #3-6
7. Move files from Desktop to screenshots folder
8. Repeat for next device

## ⏱ Estimated Time

- Per device: ~10 minutes (6 screens × ~90 seconds each)
- Total for all required: ~40 minutes
- With optional iPhone 8 Plus: ~50 minutes

## 🎯 Device Priority Order

1. **iPhone 15 Pro Max** (6.7") - Most important, do first
2. **Pixel 9 Pro** (Android) - Required for Play Store
3. **iPhone 14 Plus** (6.5") - Required for App Store
4. **iPhone 8 Plus** (5.5") - Nice to have

---

**Ready? Run the launcher script and start with iPhone 15 Pro Max!**

```bash
./scripts/launch_for_screenshots.sh
```
