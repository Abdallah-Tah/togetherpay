# Coming Soon Features - TogetherPay

This document tracks features that are planned for future releases and how they're displayed in the app.

## Implementation Summary

### Components Created

#### Mobile App (Flutter)
- **ComingSoonCard** - Reusable card widget for feature previews
- **ComingSoonScreen** - Full-screen Coming Soon page template
- **ReportsScreen** - Example implementation with 4 Coming Soon cards

#### Web App (Livewire)
- **coming-soon-card.blade.php** - Reusable Flux UI card component
- **coming-soon.blade.php** - Full-page Coming Soon template
- **reports.blade.php** - Detailed Reports & Analytics preview

---

## Post-MVP Feature Roadmap

### Q1 2025

#### ✅ CSV Export
**Status**: In Development (65%)
**Description**: Export raw payment data to CSV format for analysis
**Benefits**:
- Custom data analysis in Excel/Google Sheets
- Accounting integration
- Historical record keeping

**Technical Requirements**:
- Laravel Excel package
- CSV generation service
- Secure file download with signed URLs
- Date range filtering

---

### Q2 2025

#### 📊 Payment Analytics
**Status**: Planned
**Description**: Detailed insights into payment patterns and collection rates
**Features**:
- Collection rate graphs
- Payment timeline visualization
- Member performance comparison
- Late payment trend analysis
- Payment method distribution

**Technical Requirements**:
- Chart.js or similar for graphs
- Analytics data aggregation service
- Caching layer for performance
- Real-time data updates

#### 📄 PDF Reports
**Status**: Planned
**Description**: Generate professional formatted reports
**Features**:
- Monthly/quarterly summaries
- Group performance reports
- Individual member statements
- Custom branding options

**Technical Requirements**:
- Laravel DomPDF package
- PDF template system
- Background job processing
- Cloud storage for generated PDFs

#### 📱 SMS Reminders
**Status**: Planned
**Description**: Send payment reminders via text message
**Features**:
- Configurable reminder schedules
- Multi-stage reminder campaigns
- SMS delivery tracking
- International SMS support

**Technical Requirements**:
- Twilio or similar SMS service
- Phone number verification
- SMS template system
- Delivery status webhooks
- Cost tracking and limits

---

### Q3 2025

#### 💳 Card Payments & Auto-Charge
**Status**: Research Phase
**Description**: Premium feature for automatic payment processing
**Features**:
- Secure card vaulting
- Automatic charge scheduling
- PCI-compliant processing
- Multiple payment methods
- Failed payment retry logic

**Technical Requirements**:
- Stripe/Braintree integration
- PCI DSS compliance
- Card tokenization
- Webhook handling for payment events
- Fraud detection
- Dispute management

#### 📧 Scheduled Reports
**Status**: Planned
**Description**: Automated report delivery via email
**Features**:
- Weekly/monthly report schedules
- Customizable report templates
- Email delivery preferences
- Multiple recipient support

**Technical Requirements**:
- Laravel scheduler
- Queue system for background processing
- Email template system
- Report generation service

---

### Q4 2025

#### 🌍 Multi-Currency Support
**Status**: Planning
**Description**: Support for international payments
**Features**:
- Multiple currency options
- Real-time exchange rates
- Currency conversion tracking
- Multi-country payment methods

**Technical Requirements**:
- Currency exchange API integration
- Multi-currency database schema
- Localization support
- Country-specific payment methods

#### 🎫 Virtual Card
**Status**: Research Phase
**Description**: Virtual card for direct carrier payments
**Features**:
- Single-use virtual card numbers
- Direct carrier bill payment
- Transaction tracking
- Automatic reconciliation

**Technical Requirements**:
- Banking/fintech partner integration
- Virtual card API
- Transaction monitoring
- Compliance requirements

#### 👥 Multi-Owner Roles
**Status**: Planned
**Description**: Support for multiple group administrators
**Features**:
- Role-based permissions
- Co-owner management
- Action audit logs
- Permission inheritance

**Technical Requirements**:
- Enhanced permission system
- Role management UI
- Audit logging
- Activity notifications

---

## How to Add New Coming Soon Features

### Mobile App

1. **Import the widget**:
```dart
import '../../widgets/coming_soon_card.dart';
```

2. **Add a card to your screen**:
```dart
const ComingSoonCard(
  icon: Icons.your_icon,
  title: 'Feature Name',
  description: 'Detailed description of what this feature will do.',
  estimatedRelease: 'Q2 2025',
  accentColor: Colors.blue, // Choose theme color
)
```

3. **Or create a full screen**:
```dart
ComingSoonScreen(
  icon: Icons.your_icon,
  title: 'Feature Name',
  description: 'Detailed description...',
  estimatedRelease: 'Q2 2025',
)
```

### Web App (Livewire)

1. **Use the card component**:
```blade
<livewire:components.coming-soon-card
    icon="your-icon"
    title="Feature Name"
    description="Detailed description..."
    estimated-release="Q2 2025"
    accent-color="primary"
/>
```

2. **Or create a custom page**:
```blade
<?php
use function Livewire\Volt\{state};

state([
    'icon' => 'your-icon',
    'title' => 'Feature Name',
    'description' => 'Description...',
    'estimatedRelease' => 'Q2 2025',
]);
?>

<div>
    <x-app-layout>
        <!-- Use the coming-soon template or create custom layout -->
        @include('livewire.coming-soon')
    </x-app-layout>
</div>
```

---

## Design Guidelines

### Color Scheme
- **Analytics/Reports**: Indigo (#4F46E5)
- **Payments/Financial**: Green (#10B981)
- **Documents/PDF**: Red (#EF4444)
- **Communication/Email**: Blue (#3B82F6)
- **Security**: Purple (#8B5CF6)
- **Premium Features**: Gold (#F59E0B)

### Badge Colors
- **Coming Soon**: Orange (#F97316)
- **In Development**: Blue (#3B82F6)
- **Beta**: Purple (#8B5CF6)
- **Premium**: Gold (#F59E0B)

### Release Timeline Labels
- **Q1 2025**: January - March
- **Q2 2025**: April - June
- **Q3 2025**: July - September
- **Q4 2025**: October - December

---

## Tracking Progress

Update the estimated release dates quarterly based on:
- Development progress (track in GitHub Projects)
- Resource availability
- Feature dependencies
- User feedback priorities

When a feature is ready:
1. Remove Coming Soon badge
2. Update documentation
3. Announce to users via email/notification
4. Update roadmap
5. Archive Coming Soon components for that feature

---

## User Communication

### In-App
- Coming Soon badges on menu items
- Feature preview screens
- Progress indicators where applicable
- "Notify Me" buttons (future: actually send notifications)

### Email Updates
- Quarterly roadmap updates
- Feature launch announcements
- Beta testing invitations
- Feedback surveys

### Support Channels
- FAQ section for upcoming features
- Roadmap visibility in documentation
- Feature request voting system (planned)

---

## Technical Debt & Prerequisites

### Before Implementing Card Payments
- [ ] PCI DSS compliance audit
- [ ] Legal review of terms
- [ ] Payment processor partnership
- [ ] Enhanced security measures
- [ ] Fraud detection system

### Before Implementing Multi-Currency
- [ ] International banking relationships
- [ ] Currency exchange API selection
- [ ] Tax compliance for multiple jurisdictions
- [ ] Localization framework
- [ ] Regional payment method research

### Before Implementing Virtual Cards
- [ ] Banking/fintech partner selection
- [ ] Regulatory compliance review
- [ ] Card network partnerships
- [ ] Transaction monitoring system
- [ ] Customer support training

---

## Metrics to Track

### Feature Adoption
- Number of users accessing Coming Soon screens
- Click-through rates on "Notify Me" buttons
- Feature request volume by category
- User feedback sentiment

### Development Progress
- Story points completed per quarter
- Sprint velocity
- Bug resolution time
- Test coverage percentage

### Business Impact
- Revenue impact of premium features
- User retention after feature launches
- Support ticket reduction
- User satisfaction scores

---

Last Updated: 2025-01-09
Next Review: 2025-04-01 (Q2 Planning)
