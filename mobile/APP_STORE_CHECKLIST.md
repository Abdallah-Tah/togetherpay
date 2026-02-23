# TogetherPay - App Store Submission Checklist

**Version:** 1.0.5+15
**Target Launch:** TBD
**Markets:** USA Only

---

## 📱 Apple App Store Submission

### 1. Apple Developer Account
- [ ] Enrolled in Apple Developer Program ($99/year)
- [ ] Team ID and certificates configured
- [ ] App Store Connect account setup

### 2. App Store Connect - App Information

**App Name:** TogetherPay

**Subtitle (max 30 chars):** Split bills with friends easily

**Primary Category:** Finance
**Secondary Category:** Productivity

**Keywords (max 100 chars):**
```
bill split, group payments, venmo, cash app, expense, share, roommate, family plan
```

**Support URL:** https://togetherpay.app/contact
**Marketing URL:** https://togetherpay.app
**Privacy Policy URL:** https://togetherpay.app/privacy
**Terms of Service URL:** https://togetherpay.app/terms

### 3. App Description

**Short Description (170 chars max):**
```
Split recurring bills effortlessly! Track payments from friends and family via Venmo or Cash App. Perfect for shared phone plans, subscriptions, and group expenses.
```

**Full Description:**
```
TogetherPay makes splitting recurring bills a breeze!

PERFECT FOR:
• Family phone plans
• Shared streaming subscriptions
• Roommate utilities
• Group memberships
• Any recurring group expense

KEY FEATURES:
✓ Create unlimited groups
✓ Add members with custom payment amounts
✓ Send payment requests via Venmo or Cash App
✓ Track payment status in real-time
✓ Automatic reminders for pending payments
✓ Payment history and reporting
✓ Secure Square integration for card payments (optional)

HOW IT WORKS:
1. Create a group (e.g., "Family Phone Plan")
2. Add members and set their contribution
3. Choose Venmo or Cash App for payments
4. Send payment requests automatically
5. Track who paid and who's pending
6. Get paid on time, every time!

PAYMENT OPTIONS:
• Venmo deep links (fee-free)
• Cash App deep links (fee-free)
• Card/Apple Pay via Square (premium)

PRIVACY & SECURITY:
• Your payment info stays private
• We never handle actual money
• End-to-end secure connections
• No hidden fees

Perfect for anyone managing shared expenses with friends, family, or roommates. Say goodbye to awkward payment reminders and hello to stress-free bill splitting!

USA only. Requires Venmo or Cash App account for payments.
```

### 4. Screenshots Required

> **📸 Screenshot Tools Available:**
> - Quick launcher: `./scripts/launch_for_screenshots.sh`
> - Full guide: See `SCREENSHOT_GUIDE.md`
> - Quick reference: See `QUICK_SCREENSHOT_REFERENCE.md`

**iPhone 6.9" Display (1320 x 2868)** - iPhone 16 Pro Max [REQUIRED]
- [ ] 1. Onboarding/Welcome screen
- [ ] 2. Dashboard with groups
- [ ] 3. Group details with members
- [ ] 4. Add member screen (showing payment channels)
- [ ] 5. Payment requests list
- [ ] 6. Payment request detail (with Venmo/Cash App buttons)

**iPhone 6.7" Display (1290 x 2796)** - iPhone 16 Plus [REQUIRED]
- [ ] Same 6 screenshots resized

**iPad Pro 12.9" (2048 x 2732)** (If supporting iPad)
- [ ] Same 6 screenshots optimized for iPad

### 5. App Preview Video (Optional but Recommended)

**Duration:** 15-30 seconds
**Shows:**
- Creating a group
- Adding members
- Sending payment request
- Member receiving and paying
- Owner confirming payment

### 6. App Store Review Information

**Demo Account:**
```
Email: demo@togetherpay.app
Password: [Create a test account that works]
```

**Review Notes:**
```
TogetherPay is a bill-splitting app for recurring group expenses.

TESTING INSTRUCTIONS:
1. Sign in with demo account above
2. Navigate to "Family Phone Plan" group
3. View members and payment requests
4. Click "Send Test Request" to simulate payment flow
5. Payment links open Venmo/Cash App (install required to test)

PAYMENT FLOW:
- App generates deep links to Venmo/Cash App
- We don't process payments directly
- Two-step confirmation: member marks → owner confirms

Note: Square card payments require Square merchant account (optional feature).

Contact: support@togetherpay.app
```

### 7. Age Rating

- [ ] **4+** (No objectionable content)
- Alcohol, Tobacco, Drugs: None
- Contests: None
- Gambling: None
- Graphic/Sexual Content: None
- Violence: None

### 8. Pricing & Availability

- [ ] **Free** (with optional in-app purchases)
- [ ] Available in: **United States only**
- [ ] Release: **Manual release after approval**

### 9. In-App Purchases (If applicable)

**Premium Subscription:**
- Product ID: `togetherpay_premium_monthly`
- Price: $4.99/month
- Description: "Square card payments and advanced features"

### 10. Build Upload

```bash
# iOS build commands
cd mobile
flutter build ios --release
open ios/Runner.xcworkspace

# In Xcode:
# 1. Select "Product > Archive"
# 2. Click "Distribute App"
# 3. Select "App Store Connect"
# 4. Upload build
```

- [ ] Build uploaded via Xcode
- [ ] Build processed and ready
- [ ] Assigned to version 1.0.5

---

## 🤖 Google Play Store Submission

### 1. Google Play Console Setup

- [ ] Google Play Developer account ($25 one-time)
- [ ] App created in Play Console
- [ ] Internal testing track setup

### 2. Store Listing

**App Name:** TogetherPay

**Short Description (80 chars max):**
```
Split recurring bills with friends. Track Venmo & Cash App payments effortlessly.
```

**Full Description (4000 chars max):**
```
TogetherPay - The easiest way to split recurring bills!

Tired of chasing friends for their share? TogetherPay automates payment requests and tracking for any group expense.

🎯 PERFECT FOR
• Family phone plans
• Shared streaming (Netflix, Spotify, etc.)
• Roommate utilities (electricity, internet)
• Group memberships (gym, Costco)
• Any recurring bill split between people

✨ KEY FEATURES

EASY GROUP MANAGEMENT
• Create unlimited groups
• Add members with custom amounts or percentages
• Set recurring payment schedules
• Automatic payment request generation

FLEXIBLE PAYMENT OPTIONS
• Venmo deep links (fee-free)
• Cash App deep links (fee-free)
• Card payments via Square (premium)
• Member chooses preferred payment method

SMART TRACKING
• Real-time payment status updates
• Two-step confirmation (member marks, owner confirms)
• Payment history and analytics
• Automated reminder notifications

OWNER BENEFITS
• Dashboard view of all groups
• Track who paid and who's pending
• Send gentle nudge reminders
• Export payment reports
• Issue resolution workflow

MEMBER BENEFITS
• View upcoming payments
• One-tap payment links
• Payment history tracking
• Email notifications

🔒 PRIVACY & SECURITY
• Your payment details stay private
• We never handle actual money transfers
• Secure end-to-end connections
• Activity logging for transparency

💰 PRICING
• Free forever for basic features
• Premium: $4.99/month for Square card payments

HOW IT WORKS
1. Create a group (e.g., "Roommate Utilities")
2. Add members with their contribution amounts
3. Set member's preferred payment channel
4. Payment requests sent automatically each cycle
5. Members click Venmo/Cash App link to pay
6. Mark payments confirmed when received
7. Never chase payments again!

Perfect for families, roommates, friends, and any group sharing recurring expenses. Join thousands managing bills stress-free!

📍 Available in USA only
📧 Support: support@togetherpay.app
🌐 Website: togetherpay.app

Note: Requires Venmo or Cash App account for peer-to-peer payments. Optional Square integration for card payments.
```

### 3. Screenshots Required

**Phone (16:9 ratio)** - Min 2, Max 8
- [ ] 1. Dashboard with groups (1080 x 1920)
- [ ] 2. Group details with members
- [ ] 3. Add member with payment channels
- [ ] 4. Payment requests list
- [ ] 5. Payment request detail
- [ ] 6. Reports/analytics screen

**7" Tablet (Optional)**
- [ ] Same screenshots at tablet resolution (1024 x 1768)

**10" Tablet (Optional)**
- [ ] Same screenshots at large tablet resolution (1280 x 1920)

### 4. Graphic Assets

**App Icon:**
- [ ] 512 x 512 PNG (32-bit)
- [ ] Transparent background not allowed

**Feature Graphic:**
- [ ] 1024 x 500 PNG or JPEG
- [ ] Showcases app name and key feature

**Promo Video (Optional):**
- [ ] YouTube URL
- [ ] 30 seconds to 2 minutes

### 5. Categorization

**App Category:** Finance
**Tags:**
- bill splitting
- expense tracking
- payment management
- group payments

### 6. Content Rating

Answer questionnaire:
- [ ] No violent content
- [ ] No sexual content
- [ ] No alcohol/drugs/tobacco
- [ ] Financial transactions: Yes (bill splitting)
- Result: **ESRB Everyone**

### 7. Pricing & Distribution

- [ ] **Free** app
- [ ] In-app products: Yes (Premium subscription)
- [ ] Countries: **United States only**
- [ ] Content rating: Approved

### 8. App Content

**Privacy Policy:** https://togetherpay.app/privacy
**Terms of Service:** https://togetherpay.app/terms

**Ads:** No

**Target Audience:** Everyone

**Data Safety:**
```
Data Collected:
- Email address (account creation)
- Name (account creation)
- Payment preferences (Venmo/Cash App username)
- Transaction history (payment status only, not amounts)

Data Shared: None

Data Security:
- Encrypted in transit
- Can request data deletion
- No data sold to third parties
```

### 9. Build Upload

```bash
# Android build commands
cd mobile
flutter build appbundle --release

# Upload to Play Console
# File: build/app/outputs/bundle/release/app-release.aab
```

- [x] Build uploaded (v1.0.5+15)
- [ ] Deobfuscation file uploaded
- [ ] Internal testing track published

---

## 📝 Legal & Compliance

### Privacy Policy Must Include:
- [ ] What data is collected (email, name, payment handles)
- [ ] How data is used (payment routing, notifications)
- [ ] Data retention policy
- [ ] User rights (access, deletion)
- [ ] CCPA compliance (California users)
- [ ] Contact information

### Terms of Service Must Include:
- [ ] Acceptable use policy
- [ ] Payment disclaimer (no liability for P2P transfers)
- [ ] Account termination conditions
- [ ] Limitation of liability
- [ ] Dispute resolution
- [ ] USA jurisdiction

---

## 🧪 Pre-Launch Testing

### Test on Real Devices:
- [ ] iPhone 15 Pro (iOS 17+)
- [ ] iPhone SE (smaller screen)
- [ ] Google Pixel 8 (Android 14)
- [ ] Samsung Galaxy (Android 13)

### Critical Flows to Test:
- [ ] Sign up new account
- [ ] Create first group
- [ ] Add member with Venmo
- [ ] Add member with Cash App
- [ ] Send payment request
- [ ] Receive payment link in email
- [ ] Click Venmo/Cash App link (opens app)
- [ ] Mark payment as sent
- [ ] Owner confirms payment
- [ ] View payment history
- [ ] Push notifications work

---

## 📊 Marketing Materials

### Website Landing Page:
- [ ] Hero section with app preview
- [ ] Feature highlights
- [ ] Testimonials (if available)
- [ ] App Store badges
- [ ] Email signup for launch

### Social Media:
- [ ] Twitter account created
- [ ] Instagram account created
- [ ] Launch announcement prepared
- [ ] Demo video ready

---

## ✅ Final Pre-Submission Checklist

### Both Stores:
- [ ] All screenshots captured and optimized
- [ ] App descriptions proofread
- [x] Privacy Policy live and linked (https://togetherpay.app/privacy)
- [x] Terms of Service live and linked (https://togetherpay.app/terms)
- [x] Support email monitored (support@togetherpay.com)
- [ ] Demo account created and working
- [ ] All features tested on production backend

### Apple Specific:
- [ ] TestFlight beta tested (optional but recommended)
- [ ] Review notes clear and detailed
- [ ] Export compliance answered (No encryption)

### Google Specific:
- [ ] Internal testing completed
- [ ] Deobfuscation files uploaded
- [ ] Data safety section completed
- [ ] Content rating questionnaire approved

---

## 🚀 Submission Timeline

**Week 1:**
- Prepare all screenshots
- Finalize descriptions
- Upload builds to both stores

**Week 2:**
- Submit for review
- Address any reviewer feedback
- Wait for approval (Apple: 1-3 days, Google: 1-7 days)

**Week 3:**
- Launch! 🎉
- Monitor crash reports
- Respond to user reviews
- Fix critical bugs immediately

---

## 📞 Support

**Before Launch:**
- Create support@togetherpay.app email
- Setup auto-responder
- Prepare FAQ document
- Create help center (or use existing)

**Post-Launch:**
- Monitor App Store reviews
- Respond to user feedback within 24h
- Track crash reports via Sentry
- Update app based on user requests

---

**Status:** Ready for screenshot generation
**Next Step:** Generate all required screenshots
**Contact:** [Your email]
