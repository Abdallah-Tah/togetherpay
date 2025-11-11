<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ClearSquareCredentials extends Command
{
    protected $signature = 'square:clear-credentials {--user-id=1 : The user ID to clear}';
    protected $description = 'Clear Square credentials for a user to allow re-authentication';

    public function handle(): void
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }

        $user->update([
            'square_access_token' => null,
            'square_merchant_id' => null,
            'square_location_id' => null,
            'square_connected_at' => null,
        ]);

        $this->info("✓ Cleared Square credentials for: {$user->name}");
        $this->line("Go to Settings > Square Account and click 'Connect Square' to re-authenticate");
    }
}
