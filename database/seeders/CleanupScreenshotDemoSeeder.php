<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CleanupScreenshotDemoSeeder extends Seeder
{
    /**
     * Clean up demo data created for screenshots
     * Run with: php artisan db:seed --class=CleanupScreenshotDemoSeeder
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@togetherpay.app')->first();

        if (! $demoUser) {
            $this->command->warn('No demo user found. Nothing to clean up.');

            return;
        }

        // Delete all groups (cascade will delete members and requests)
        $groupCount = $demoUser->groups()->count();
        $demoUser->groups()->delete();

        // Optionally delete the demo user account too
        $this->command->info("Deleted {$groupCount} demo groups and associated data.");

        if ($this->command->confirm('Delete the demo user account too?', true)) {
            $demoUser->delete();
            $this->command->info('✅ Demo user deleted: demo@togetherpay.app');
        } else {
            $this->command->info('✅ Demo user kept: demo@togetherpay.app');
        }

        $this->command->info('🎉 Cleanup complete!');
    }
}
