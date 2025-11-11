<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\Square\SquareInvoiceService;
use App\Services\Square\SquareCustomerService;
use Illuminate\Console\Command;

class TestRealSquareInvoice extends Command
{
    protected $signature = 'test:real-square-invoice {email} {phone?} {--force : Skip confirmation}';

    protected $description = 'Create a REAL Square invoice that will send to your email/Cash App';

    public function handle(): void
    {
        $email = $this->argument('email');
        $phone = $this->argument('phone');

        $this->info('=== Creating REAL Square Invoice ===');
        $this->info('This will send an actual invoice to your email and Cash App');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to create a REAL invoice?')) {
                $this->info('Cancelled.');
                return;
            }
        }

        // Check Square credentials
        // FULL token from working curl command (84 characters)
        $squareToken = 'EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8';
        $this->info('Using Square Access Token: ' . ($squareToken ? 'FOUND' : 'NOT FOUND'));
        $this->line('Token length: ' . strlen($squareToken) . ' characters');

        // Show environment settings
        $this->line('Environment: ' . config('payment_requests.square.mode'));
        $this->line('Config Token: ' . (config('payment_requests.square.access_token') ? 'SET' : 'NOT SET'));
        $this->newLine();
        // curl https://connect.squareupsandbox.com/v2/customers \
        //   -H 'Square-Version: 2025-01-23' \
        //   -H 'Authorization: Bearer EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8' \
        //   -H 'Content-Type: application/json' \
        //   -d '{"idempotency_key":"test1234","given_name":"Test","email_address":"test@example.com"}'
        /*
 * Removed accidental raw curl command which breaks PHP parsing.
 * If you want to call the Square API here, use Laravel's HTTP client:
 *
 * use Illuminate\Support\Facades\Http;
 *
 * $response = Http::withHeaders([
 *     'Square-Version' => '2025-01-23',
 *     'Authorization' => 'Bearer ' . $squareToken,
 *     'Content-Type' => 'application/json',
 * ])->post('https://connect.squareupsandbox.com/v2/customers', [
 *     'idempotency_key' => 'test1234',
 *     'given_name' => 'Test',
 *     'email_address' => 'test@example.com',
 * ]);
 *
 * // then check $response->successful() or handle errors as needed.
 */
        try {
            if ($squareToken) {
                $this->info('Verifying Square token by listing customers...');

                // Use sandbox URL since this is a sandbox token
                $baseUrl = config('payment_requests.square.mode') === 'production'
                    ? 'https://connect.squareup.com'
                    : 'https://connect.squareupsandbox.com';

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Square-Version' => '2025-01-23',
                    'Authorization' => 'Bearer ' . $squareToken,
                    'Content-Type' => 'application/json',
                ])->get($baseUrl . '/v2/customers');

                if ($response->successful()) {
                    $this->info('Square API reachable and token looks valid.');
                    $this->line('  Environment: ' . config('payment_requests.square.mode'));
                    $this->line('  Base URL: ' . $baseUrl);
                } else {
                    $this->warn('Square API returned HTTP ' . $response->status() . '. Response: ' . substr($response->body(), 0, 1000));
                }
            } else {
                $this->warn('Skipping Square API check because no token provided.');
            }
        } catch (\Exception $e) {
            $this->warn('Square API check failed: ' . $e->getMessage());
        }


        if (!$squareToken) {
            $this->error('Square access token not configured!');
            $this->line('Set SQUARE_ACCESS_TOKEN in .env');
            return;
        }

        $this->newLine();
        $this->info('Step 1: Creating test owner with Square...');

        // Create owner
        $owner = User::factory()->create([
            'name' => 'Test Owner',
            'email' => 'owner-test-' . time() . '@togetherpay.local',
            'phone' => '+12065551111',
        ]);

        // Connect to Square
        $owner->update([
            'square_merchant_id' => 'TEST_MERCHANT_' . time(),
            'square_access_token' => encrypt($squareToken),
            'square_location_id' => config('payment_requests.square.default_location_id') ?: 'MAIN',
            'square_connected_at' => now(),
        ]);

        // Verify encryption/decryption works
        $this->line("✓ Owner created");
        $this->line("  Testing token encryption...");
        try {
            $decryptedToken = decrypt($owner->square_access_token);
            $tokenMatch = $decryptedToken === $squareToken;
            $this->line("  Encrypted token length: " . strlen($owner->square_access_token));
            $this->line("  Decrypted token matches: " . ($tokenMatch ? 'YES ✓' : 'NO ✗'));
            $this->line("  Decrypted token: " . substr($decryptedToken, 0, 20) . '...');
        } catch (\Exception $e) {
            $this->error("  Decryption failed: " . $e->getMessage());
        }
        $this->newLine();

        // Create group
        $this->info('Step 2: Creating test group...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'TogetherPay Test Group',
            'description' => 'Testing Square + Cash App Pay integration',
            'total_amount_cents' => 500, // $5.00
            'billing_mode' => 'auto_square',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        $this->line("✓ Group created: {$group->name}");
        $this->newLine();

        // Create member (YOU)
        $this->info('Step 3: Creating member with YOUR details...');
        $memberUser = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Abdallah Mohamed',
                'phone' => $phone ?: '+12074097887',
                'password' => bcrypt('password'),
            ]
        );

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => '$AbdallahTM',
            'cash_phone' => $phone,
            'cash_email' => $email,
            'share_type' => 'AMOUNT',
            'share_value' => 5.00,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->line("✓ Member created");
        $this->line("  Email: {$email}");
        $this->line("  Phone: {$phone}");
        $this->line("  Cash App: \$AbdallahTM");
        $this->newLine();

        // Create billing cycle
        $this->info('Step 4: Creating billing cycle...');
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'sequence_number' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'total_amount_cents' => 500,
            'total_collected_cents' => 0,
            'status' => 'active',
        ]);

        $this->line("✓ Cycle created");
        $this->newLine();

        // Create Square customer
        $this->info('Step 5: Creating Square customer...');
        try {
            // Refresh owner to ensure we have the latest data
            $owner->refresh();

            $customerService = app(SquareCustomerService::class);
            $customerId = $customerService->createCustomerForMember($member, $owner);

            $member->update(['square_customer_id' => $customerId]);

            $this->line("✓ Square customer created: {$customerId}");
        } catch (\Exception $e) {
            $this->error("Failed to create Square customer: {$e->getMessage()}");
            $this->newLine();
            $this->line("Using mock customer ID for testing...");
            $member->update(['square_customer_id' => 'MOCK_' . time()]);
        }
        $this->newLine();

        // Create Square invoice
        $this->info('Step 6: Creating REAL Square invoice...');
        $this->line('This will send an email to: ' . $email);
        $this->newLine();

        try {
            // Refresh owner to ensure we have the latest data
            $owner->refresh();

            $invoiceService = app(SquareInvoiceService::class);
            $invoiceId = $invoiceService->createInvoiceForMember($member, $cycle);

            $this->line("✓ Square invoice created and published!");
            $this->line("  Invoice ID: {$invoiceId}");
            $this->newLine();

            $this->info('=== SUCCESS! ===');
            $this->newLine();
            $this->line('<fg=green>Check your email:</> ' . $email);
            $this->line('You should receive an email from Square with:');
            $this->line('  • Subject: "Invoice from Test Owner"');
            $this->line('  • Amount: $5.00');
            $this->line('  • Payment link inside');
            $this->newLine();
            $this->line('<fg=yellow>When you click the payment link:</>');
            $this->line('  1. Opens Square payment page');
            $this->line('  2. Click "Pay with Cash App"');
            $this->line('  3. Cash App opens on your phone');
            $this->line('  4. Shows: "$5.00 to Test Owner"');
            $this->line('  5. Swipe/tap to confirm');
            $this->line('  6. Payment completes!');
            $this->newLine();
            $this->line('<fg=cyan>Check Cash App Activity:</>');
            $this->line('You should see a notification in Cash App:');
            $this->line('  "TogetherPay Test Group - $5.00"');
            $this->newLine();

            $this->info('Test Data IDs:');
            $this->line("  Owner ID: {$owner->id}");
            $this->line("  Group ID: {$group->id}");
            $this->line("  Member ID: {$member->id}");
            $this->line("  Cycle ID: {$cycle->id}");
            $this->line("  Invoice ID: {$invoiceId}");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('Failed to create Square invoice!');
            $this->newLine();
            $this->line('Error: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
