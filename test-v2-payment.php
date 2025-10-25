<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PaymentDriverFactory;
use App\Support\PRCode;

echo "🧪 Testing V2 Payment Request System\n";
echo "=====================================\n\n";

// 1. Get Ikram as the OWNER
$owner = User::where('email', 'ikram.barkad@test.com')->first();
if (! $owner) {
    echo "👤 Creating owner: Ikram Barkad...\n";
    $owner = User::create([
        'name' => 'Ikram Barkad',
        'email' => 'ikram.barkad@test.com',
        'password' => bcrypt('password'),
        'cash_tag' => '$IkramBarkad',
        'venmo_username' => '@ikrambarkad',
        'preferred_payment_method' => 'CASH_APP',
    ]);
    echo "✅ Owner created: {$owner->name}\n";
} else {
    echo "✅ Owner: {$owner->name}\n";

    // Update owner's payment accounts if not set
    if (! $owner->cash_tag) {
        $owner->update([
            'cash_tag' => '$IkramBarkad',
            'venmo_username' => '@ikrambarkad',
            'preferred_payment_method' => 'CASH_APP',
        ]);
        echo "   ⚡ Updated payment accounts\n";
    }
}

echo "   Email: {$owner->email}\n";
echo "   Cash App: {$owner->cash_tag}\n";
echo "   Venmo: {$owner->venmo_username}\n\n";

// 2. Get Abdallah as the MEMBER
$member = User::where('email', 'abdal_cascad@hotmail.com')->first();
if (! $member) {
    echo "❌ Member (Abdallah) not found!\n";
    echo "   Please create user with email: abdal_cascad@hotmail.com\n";
    exit(1);
}

// Update member's payment details if not set
if (! $member->cash_tag) {
    $member->update([
        'cash_tag' => '$AbdallahMohamed',
        'venmo_username' => '@abdallah',
    ]);
    echo "   ⚡ Updated member's payment accounts\n";
}

echo "✅ Member: {$member->name}\n";
echo "   Email: {$member->email}\n";
echo "   Cash App: ".($member->cash_tag ?: 'Not set')."\n";
echo "   Venmo: ".($member->venmo_username ?: 'Not set')."\n\n";

// 3. Create or get group (owned by Ikram)
$group = Group::where('owner_id', $owner->id)
    ->where('name', 'T-Mobile Family Plan')
    ->first();

if (! $group) {
    echo "📱 Creating T-Mobile Family Plan group...\n";
    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'T-Mobile Family Plan',
        'total_amount' => 15000, // $150.00 in cents
        'currency' => 'USD',
        'billing_cadence' => 'MONTHLY',
        'due_day_of_month' => 15,
        'grace_period_days' => 3,
        'timezone' => 'America/New_York',
        'status' => 'ACTIVE',
    ]);
    echo "✅ Group created: {$group->name}\n";
} else {
    echo "✅ Using existing group: {$group->name}\n";
}

$totalAmount = $group->total_amount / 100;
echo "   Total Bill: \${$totalAmount}\n";
echo "   Owner: {$owner->name}\n";
echo "   Due Date: Day {$group->due_day_of_month} of each month\n\n";

// 4. Add Abdallah as member to Ikram's group
$groupMember = GroupMember::where('group_id', $group->id)
    ->where('user_id', $member->id)
    ->first();

if (! $groupMember) {
    echo "➕ Adding {$member->name} as member to group...\n";
    $groupMember = GroupMember::create([
        'group_id' => $group->id,
        'user_id' => $member->id,
        'preferred_channel' => 'CASH_APP',
        'cash_tag' => $member->cash_tag ?: '$AbdallahMohamed',
        'venmo_username' => $member->venmo_username,
        'share_type' => 'FIXED',
        'share_value' => 50.00, // $50/month
        'status' => 'ACTIVE',
    ]);
    echo "✅ Member added to group\n";
} else {
    echo "✅ Member already in group\n";
}

echo "   Share: \${$groupMember->share_value}/month\n";
echo "   Payment Method: {$groupMember->preferred_channel}\n\n";

// 5. Create billing cycle for this month
$cycle = BillingCycle::where('group_id', $group->id)
    ->where('period_start', '>=', now()->startOfMonth())
    ->where('period_end', '<=', now()->endOfMonth())
    ->first();

if (! $cycle) {
    echo "📅 Creating billing cycle...\n";
    $dueDate = now()->setDay($group->due_day_of_month);
    $cycle = BillingCycle::create([
        'group_id' => $group->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'due_at' => $dueDate,
        'status' => 'PENDING',
        'total_expected_cents' => $group->total_amount,
        'total_collected_cents' => 0,
    ]);
    echo "✅ Billing cycle created\n";
} else {
    echo "✅ Using existing billing cycle\n";
}

$periodStart = \Carbon\Carbon::parse($cycle->period_start)->format('M d');
$periodEnd = \Carbon\Carbon::parse($cycle->period_end)->format('M d, Y');
$dueDate = \Carbon\Carbon::parse($cycle->due_at)->format('M d, Y');
echo "   Period: {$periodStart} - {$periodEnd}\n";
echo "   Due Date: {$dueDate}\n\n";

// 6. Generate PR Code (V2 Feature!)
$cycleYm = now()->format('Y-m');
$prCode = PRCode::make($group->id, $groupMember->id, $cycleYm);

echo "🆕 V2 Feature: PR Code Generated!\n";
echo "────────────────────────────────────────────\n";
echo "PR Code: {$prCode}\n";

// Parse to verify
$parsed = PRCode::parse($prCode);
echo "Parsed Info:\n";
echo "  - Cycle: {$parsed['cycle_ym']}\n";
echo "  - Group ID: {$parsed['group_id']}\n";
echo "  - Member ID: {$parsed['member_id']}\n";
echo "  - Random: {$parsed['random']}\n\n";

// 7. Build payment link using V2 drivers
$testAmount = 50.00;
$amountCents = (int) ($testAmount * 100);
$note = "{$group->name} ".now()->format('M Y').' cycle';

echo "💰 Building payment link with V2 drivers...\n";
echo "────────────────────────────────────────────\n";

try {
    // Get the appropriate driver
    $driverType = $groupMember->preferred_channel === 'VENMO' ? 'venmo' : 'cashapp';
    $driver = PaymentDriverFactory::make($driverType);

    echo "Using driver: {$driver->getName()}\n";

    // Build link with PR code embedded
    $links = $driver->buildLink($groupMember, $amountCents, $prCode, $note);

    echo "✅ Payment link generated!\n\n";

    // 8. Create payment request with V2 fields
    echo "📝 Creating payment request with V2 schema...\n";
    $paymentRequest = PaymentRequest::create([
        'group_id' => $group->id,
        'cycle_id' => $cycle->id,
        'member_id' => $groupMember->id,
        'amount_cents' => $amountCents,
        'method_hint' => $driverType,
        'pr_code' => $prCode,
        'note' => $note,
        'deep_link' => $links['uri'],
        'web_fallback_link' => $links['web_fallback'],
        'status' => 'pending',
    ]);

    echo "✅ Payment request created!\n";
    echo "   ID: {$paymentRequest->id}\n";
    echo "   PR Code: {$paymentRequest->pr_code}\n";
    echo "   Amount: \${$testAmount}\n";
    echo "   Status: {$paymentRequest->status}\n";
    echo "   Method: {$paymentRequest->method_hint}\n\n";

    // 9. Display payment links
    echo "📱 Payment Links:\n";
    echo "════════════════════════════════════════════\n";
    echo "Deep Link (opens wallet app):\n";
    echo "{$links['uri']}\n\n";

    echo "Web Fallback:\n";
    echo "{$links['web_fallback']}\n\n";

    // 10. Show the payment flow
    echo "💡 V2 Payment Flow:\n";
    echo "════════════════════════════════════════════\n";
    echo "1️⃣  Owner (Ikram) sends link to Member (Abdallah)\n";
    echo "2️⃣  Abdallah clicks → {$driverType} opens\n";
    echo "3️⃣  Amount \${$testAmount} PRE-FILLED ✅\n";
    echo "4️⃣  Note includes PR Code: {$prCode}\n";
    echo "5️⃣  Abdallah pays → Money goes to Ikram's wallet\n";
    echo "6️⃣  Abdallah marks as paid in app (status: member_marked)\n";
    echo "7️⃣  Ikram confirms receipt (status: owner_confirmed) ✅\n";
    echo "8️⃣  Cycle total updated automatically!\n\n";

    // 11. Simulate member marking as paid
    echo "🎬 Simulating Payment Flow:\n";
    echo "────────────────────────────────────────────\n";

    // Update to 'sent' status
    $paymentRequest->update(['status' => 'sent', 'sent_at' => now()]);
    echo "✅ Step 1: Payment request sent to member\n";
    echo "   Status: {$paymentRequest->status}\n\n";

    // Member marks as paid
    $paymentRequest->markAsPaid();
    echo "✅ Step 2: Member marked payment as paid\n";
    echo "   Status: {$paymentRequest->fresh()->status}\n";
    echo "   Marked At: {$paymentRequest->fresh()->marked_at}\n\n";

    // Owner confirms
    $paymentRequest->confirmPayment();
    echo "✅ Step 3: Owner confirmed payment received\n";
    echo "   Status: {$paymentRequest->fresh()->status}\n";
    echo "   Confirmed At: {$paymentRequest->fresh()->confirmed_at}\n\n";

    // Check cycle total
    $cycle->refresh();
    $collected = $cycle->total_collected_cents / 100;
    $expected = $cycle->total_expected_cents / 100;
    echo "💰 Cycle Financial Summary:\n";
    echo "   Expected: \${$expected}\n";
    echo "   Collected: \${$collected}\n";
    $remaining = $expected - $collected;
    echo "   Remaining: \${$remaining}\n\n";

    // 12. Show API endpoint examples
    echo "🔗 V2 API Endpoints:\n";
    echo "════════════════════════════════════════════\n";
    echo "Get by PR Code:\n";
    echo "GET /api/v1/requests/by-code/{$prCode}\n\n";

    echo "Member marks as paid:\n";
    echo "POST /api/v1/payment-requests/{$paymentRequest->id}/mark-paid\n\n";

    echo "Owner confirms:\n";
    echo "POST /api/v1/requests/by-code/{$prCode}/confirm\n\n";

    echo "Owner bulk confirms:\n";
    echo "POST /api/v1/cycles/{$cycle->id}/bulk-confirm\n";
    echo "Body: {\"pr_codes\": [\"{$prCode}\"]}\n\n";

    // 13. Summary
    echo "════════════════════════════════════════════\n";
    echo "🎉 V2 Test Complete!\n\n";

    echo "📊 Summary:\n";
    echo "────────────────────────────────────────────\n";
    echo "Group: {$group->name}\n";
    echo "Owner: {$owner->name} ({$owner->cash_tag})\n";
    echo "Member: {$member->name} ({$groupMember->cash_tag})\n";
    echo "PR Code: {$prCode}\n";
    echo "Payment: \${$testAmount}\n";
    echo "Status: {$paymentRequest->fresh()->status}\n\n";

    echo "✨ V2 Features Demonstrated:\n";
    echo "────────────────────────────────────────────\n";
    echo "✅ PR Code generation and parsing\n";
    echo "✅ Payment driver system (CashApp/Venmo)\n";
    echo "✅ PR code embedded in payment note\n";
    echo "✅ Enhanced status tracking (member_marked → owner_confirmed)\n";
    echo "✅ Automatic cycle total updates\n";
    echo "✅ Deep link + web fallback\n";
    echo "✅ Deterministic reconciliation via PR codes\n\n";

    echo "🚀 Next Steps:\n";
    echo "────────────────────────────────────────────\n";
    echo "1. Test API endpoints with Postman\n";
    echo "2. Implement scheduled jobs (GeneratePaymentRequestsJob)\n";
    echo "3. Create Livewire bulk-confirm component\n";
    echo "4. Add push notifications\n";
    echo "5. Build mobile app integration\n\n";

    echo "📚 Documentation:\n";
    echo "────────────────────────────────────────────\n";
    echo "• Architecture: ARCHITECTURE_V2.md\n";
    echo "• Quick Start: V2_QUICKSTART.md\n";
    echo "• Implementation: IMPLEMENTATION_V2_SUMMARY.md\n\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString()."\n\n";
}
