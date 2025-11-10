<?php

use App\Services\Square\SquareOAuthService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public ?string $merchantId = null;
    public ?string $locationId = null;
    public ?string $connectedAt = null;
    public bool $isConnected = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadSquareStatus();
    }

    /**
     * Load current Square connection status.
     */
    private function loadSquareStatus(): void
    {
        $user = Auth::user();
        $this->isConnected = $user->isSquareConfigured();
        $this->merchantId = $user->square_merchant_id;
        $this->locationId = $user->square_location_id;
        $this->connectedAt = $user->square_connected_at?->format('M d, Y g:i A');
    }

    /**
     * Redirect to Square OAuth authorization.
     */
    public function connectSquare(): void
    {
        $this->redirect(route('oauth.square.authorize'));
    }

    /**
     * Disconnect Square account.
     */
    public function disconnectSquare(): void
    {
        try {
            Auth::user()->disconnectSquare();
            $this->loadSquareStatus();
            session()->flash('status', 'Square account disconnected successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to disconnect Square account: ' . $e->getMessage());
        }
    }
}; ?>

<section class="w-full">
    <x-settings.layout :heading="__('Square Account')" :subheading="__('Manage your Square merchant connection for automatic payment collection')">
        <div class="my-6 w-full space-y-6">
            <!-- Connection Status Card -->
            <div class="rounded-lg border px-4 py-6" :class="@js($isConnected) ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900'">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            @if ($isConnected)
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Connected') }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('Your Square account is connected and ready for automatic payments') }}
                                    </p>
                                </div>
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-6v-2m0 6v2" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Not Connected') }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('Connect your Square account to enable automatic payment collection') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection Details -->
            @if ($isConnected)
                <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center justify-between py-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Merchant ID') }}
                        </span>
                        <code class="rounded bg-gray-100 px-3 py-1 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                            {{ $merchantId }}
                        </code>
                    </div>

                    @if ($locationId)
                        <div class="flex items-center justify-between border-t border-gray-200 py-3 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Location ID') }}
                            </span>
                            <code class="rounded bg-gray-100 px-3 py-1 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                {{ $locationId }}
                            </code>
                        </div>
                    @endif

                    @if ($connectedAt)
                        <div class="flex items-center justify-between border-t border-gray-200 py-3 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Connected at') }}
                            </span>
                            <span class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $connectedAt }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($isConnected)
                    <flux:button
                        wire:confirm="{{ __('Are you sure you want to disconnect your Square account? This will disable automatic payment collection for auto-square groups.') }}"
                        wire:click="disconnectSquare"
                        variant="danger"
                    >
                        {{ __('Disconnect Square') }}
                    </flux:button>
                @else
                    <flux:button
                        wire:click="connectSquare"
                        variant="primary"
                        class="flex items-center gap-2"
                    >
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" />
                        </svg>
                        {{ __('Connect Square Account') }}
                    </flux:button>
                @endif
            </div>

            <!-- Information Section -->
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium">{{ __('What is Square integration?') }}</p>
                        <p>
                            {{ __('When you enable automatic payment collection, members will receive recurring Cash App payment requests on their billing due dates. No manual payment request needed!') }}
                        </p>
                        <p class="mt-2">
                            <strong>{{ __('Note:') }}</strong>
                            {{ __('Square integration is configured per group during group creation. You can create new groups with automatic collection or stick with manual collection.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Security Note -->
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    <div class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-medium">{{ __('Security') }}</p>
                        <p>{{ __('Your Square access token is encrypted and stored securely. We never store your login credentials.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ session('status') }}
                        </p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            {{ session('error') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
