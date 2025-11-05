<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\PaymentLinkNotification;
use App\Support\PaymentLinks;

echo "🧪 Testing Cash App Payment Request\n";
echo "=====================================\n\n";

// 1. Get the owner (you)
$owner = User::where('email', 'abdal_cascad@hotmail.com')->first();
if (! $owner) {
    echo "❌ Owner not found!\n";
    exit(1);
}

echo "✅ Owner: {$owner->name}\n";
echo "   Email: {$owner->email}\n";
echo "   Cash App: {$owner->cashapp_tag}\n\n";

// 2. Create or get the T-Mobile Family Plan group
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
echo "   Due Date: Day {$group->due_day_of_month} of each month\n\n";

// 3. Find or create member (Ikram Barkad)
$member = User::where('cashapp_tag', '$IkramBarkad')->first();

if (! $member) {
    echo "👤 Creating member: Ikram Barkad...\n";
    $member = User::create([
        'name' => 'Ikram Barkad',
        'email' => 'ikram.barkad@test.com',
        'password' => bcrypt('password'),
        'cashapp_tag' => '$IkramBarkad',
        'cashapp_customer_id' => 'MMI_test_ikram_'.uniqid(), // In production, get from OAuth
        'preferred_payment_method' => 'CASH_APP',
    ]);
    echo "✅ Member created: {$member->name}\n";
} else {
    echo "✅ Using existing member: {$member->name}\n";
}

echo "   Email: {$member->email}\n";
echo "   Cash App: {$member->cashapp_tag}\n";
echo "   Customer ID: {$member->cashapp_customer_id}\n\n";

// 4. Add member to group
$groupMember = GroupMember::where('group_id', $group->id)
    ->where('user_id', $member->id)
    ->first();

if (! $groupMember) {
    echo "➕ Adding member to group...\n";
    $groupMember = GroupMember::create([
        'group_id' => $group->id,
        'user_id' => $member->id,
        'preferred_channel' => 'CASH_APP',
        'cash_tag' => $member->cashapp_tag,
        'share_type' => 'FIXED',
        'share_value' => 50.00, // $50/month
        'status' => 'ACTIVE',
    ]);
    echo "✅ Member added to group\n";
    echo "   Share: \${$groupMember->share_value}/month\n\n";
} else {
    echo "✅ Member already in group\n";
    echo "   Share: \${$groupMember->share_value}/month\n\n";
}

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

// 6. Create payment request for $5.00 (test amount)
$testAmount = 5.00;
$amountCents = (int) ($testAmount * 100);

echo "💰 Creating payment request for \${$testAmount}...\n";
$paymentRequest = PaymentRequest::create([
    'cycle_id' => $cycle->id,
    'member_id' => $groupMember->id,
    'amount_cents' => $amountCents,
    'currency' => 'USD',
    'status' => 'PENDING',
    'channel' => $groupMember->preferred_channel ?? 'CASH_APP',
    'due_at' => $cycle->due_at,
]);

echo "✅ Payment request created: PR #{$paymentRequest->id}\n";
echo "   Amount: \${$testAmount}\n";
echo "   Status: {$paymentRequest->status}\n";
$dueDateFormatted = \Carbon\Carbon::parse($paymentRequest->due_at)->format('M d, Y');
echo "   Due: {$dueDateFormatted}\n\n";

// 7. Generate P2P payment link (no API required!)
echo "� Building P2P payment link (no API)...\n";
echo "────────────────────────────────────────────\n\n";

try {
    $note = "Group {$group->name} - ".now()->format('M Y')." cycle - PR#{$paymentRequest->id}";

    // Choose channel based on member preference
    if ($groupMember->preferred_channel === 'CASH_APP' && $groupMember->cash_tag) {
        $link = PaymentLinks::cashApp($groupMember->cash_tag, $amountCents, $note);
        $channel = 'CASH_APP';
        echo "✅ Cash App deep link generated!\n\n";
    } elseif ($groupMember->preferred_channel === 'VENMO' && ! empty($groupMember->venmo_username)) {
        $link = PaymentLinks::venmo($groupMember->venmo_username, $amountCents, $note);
        $channel = 'VENMO';
        echo "✅ Venmo deep link generated!\n\n";
    } else {
        // Fallback: show a hosted page with both buttons
        $link = url("/pay/{$paymentRequest->id}");
        $channel = 'SMART';
        echo "✅ Smart Pay page link generated!\n\n";
    }

    // Update payment request with link
    $paymentRequest->update([
        'channel' => $channel,
        'metadata_json' => [
            'link' => $link,
            'note' => $note,
            'created_at' => now(),
        ],
        'status' => 'SENT',
        'sent_at' => now(),
    ]);

    echo "📱 Payment Link Details:\n";
    echo "────────────────────────────────────────────\n";
    echo "Channel: {$channel}\n";
    echo "Link: {$link}\n\n";

    if ($channel === 'SMART') {
        echo "🌐 Visit the link in a browser to see payment options\n\n";
    } else {
        $amountDisplay = number_format($testAmount, 2);
        echo "📧 What happens next:\n";
        echo "────────────────────────────────────────────\n";
        echo "1. Send this link via SMS, email, or push notification\n";
        echo "2. Member clicks link → Cash App opens\n";
        echo "3. Amount \${$amountDisplay} is PRE-FILLED! ✅\n";
        echo "4. Note is pre-filled: {$note}\n";
        echo "5. Member reviews and taps 'Pay'\n";
        echo "6. Money goes directly to your Cash App ({$owner->cashapp_tag})\n";
        echo "7. Payment complete! 🎉\n\n";
    }

    echo "💡 Try it now!\n";
    echo "────────────────────────────────────────────\n";

    if ($channel !== 'SMART') {
        // Show both deep link and web fallback
        $amountFormatted = number_format($testAmount, 2);
        echo "📱 Send this message to member:\n";
        echo "────────────────────────────────────────────\n";
        echo "Hi! Your share for {$group->name} is due:\n";
        echo "Amount: \${$amountFormatted} (auto-filled in link)\n";
        echo "Pay here: {$link}\n";
        echo "(Tap link → Cash App opens → Review → Send)\n";
        echo "────────────────────────────────────────────\n\n";

        $webLink = url("/pay/{$paymentRequest->id}");
        echo "🌐 Or use Smart Pay page:\n";
        echo "{$webLink}\n\n";
    } else {
        echo "🌐 Clickable Web Link:\n";
        echo "{$link}\n\n";
        echo "You'll see buttons for Cash App and Venmo.\n\n";
    }

    // Send notification to member
    echo "📧 Sending notification to member...\n";
    try {
        $member->notify(new PaymentLinkNotification($paymentRequest, $link));
        echo "✅ Notification sent via email!\n";
        if ($member->phone) {
            echo "✅ SMS sent to {$member->phone}\n";
        }
        echo "\n";
    } catch (\Exception $notifError) {
        echo "⚠️  Notification failed: {$notifError->getMessage()}\n";
        echo "   (This is OK for testing - member can still use the link above)\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString()."\n\n";
}

echo "════════════════════════════════════════════\n";
echo "🎉 Test Setup Complete!\n\n";

echo "📊 Summary:\n";
echo "────────────────────────────────────────────\n";
echo "Group: {$group->name}\n";
echo "Owner: {$owner->name} ({$owner->cashapp_tag})\n";
echo "Member: {$member->name} ({$member->cashapp_tag})\n";
echo "Payment Request: PR #{$paymentRequest->id} - \${$testAmount}\n";
$prStatus = $paymentRequest->status;
$prChannel = $paymentRequest->channel ?? 'N/A';
echo "Status: {$prStatus}\n";
echo "Channel: {$prChannel}\n\n";

echo "🚀 Ready to Use!\n";
echo "────────────────────────────────────────────\n";
echo "✅ No API credentials needed\n";
echo "✅ No developer account required\n";
echo "✅ Works immediately!\n\n";

echo "Next steps:\n";
echo "1. Copy the payment link above\n";
echo "2. Send it to the member via SMS/email\n";
echo "3. Member clicks → App opens → Payment sent\n";
echo "4. Mark as paid in your dashboard\n\n";

echo "💡 Alternative: Visit the Smart Pay page in a browser\n";
echo '   URL: '.url("/pay/{$paymentRequest->id}")."\n\n";
