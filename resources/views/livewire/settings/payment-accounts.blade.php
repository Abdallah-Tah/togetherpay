<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $cashapp_tag = '';
    public string $venmo_username = '';
    public string $preferred_payment_method = 'CASH_APP';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->cashapp_tag = $user->cashapp_tag ?? '';
        $this->venmo_username = $user->venmo_username ?? '';
        $this->preferred_payment_method = $user->preferred_payment_method ?? 'CASH_APP';
    }

    /**
     * Update payment accounts for the currently authenticated user.
     */
    public function updatePaymentAccounts(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'cashapp_tag' => ['nullable', 'string', 'max:255', 'regex:/^\$[a-zA-Z0-9_-]+$/'],
            'venmo_username' => ['nullable', 'string', 'max:255', 'regex:/^@?[a-zA-Z0-9_-]+$/'],
            'preferred_payment_method' => ['required', 'in:CASH_APP,VENMO,CARD'],
        ], [
            'cashapp_tag.regex' => 'Cash App tag must start with $ (e.g., $JohnDoe)',
            'venmo_username.regex' => 'Venmo username must be alphanumeric (e.g., @JohnDoe or JohnDoe)',
        ]);

        // Format Venmo username to include @ if not present
        if (!empty($validated['venmo_username']) && !str_starts_with($validated['venmo_username'], '@')) {
            $validated['venmo_username'] = '@' . $validated['venmo_username'];
        }

        $user->update($validated);

        $this->dispatch('payment-accounts-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Payment Accounts')"
        :subheading="__('Configure where you receive payments from group members')">

        <form wire:submit="updatePaymentAccounts" class="my-6 w-full space-y-6">

            <!-- Info Box -->
            <div class="rounded-xl bg-teal-50 dark:bg-teal-900/20 p-4 border border-teal-200 dark:border-teal-900/40">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-teal-800 dark:text-teal-200">Why add payment accounts?</h3>
                        <div class="mt-2 text-sm text-teal-700 dark:text-teal-300">
                            <p>When you create a group, members will be directed to send payments to your preferred account. Add your Cash App or Venmo username so members know where to send money.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preferred Payment Method -->
            <div>
                <label for="preferred_payment_method" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Preferred Payment Method <span class="text-red-500">*</span>
                </label>
                <select wire:model="preferred_payment_method"
                        id="preferred_payment_method"
                        class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <option value="CASH_APP">Cash App</option>
                    <option value="VENMO">Venmo</option>
                    <option value="CARD">Credit/Debit Card (Coming Soon)</option>
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This will be the default payment method shown to members</p>
                @error('preferred_payment_method')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Cash App Section -->
            <div class="space-y-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-teal-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.59 3.475a5.1 5.1 0 0 0-3.05-2.93 9.3 9.3 0 0 0-3.69-.46L15.5 2.8l-.31 1.38.13.07c.08.04.16.08.24.12l.01-.01c.1.05.18.1.27.16h.01c.09.05.18.1.27.16h.01c.09.06.17.12.25.18h.01c.08.06.16.12.24.18l.01.01c.08.06.16.13.23.19 0 0 .01 0 .01.01.07.06.15.12.22.19l.01.01c.07.06.14.13.21.19l.01.01c.07.06.14.13.21.19l.01.01c.07.07.13.13.2.2l.01.01c.06.07.13.13.19.2l.01.01c.06.07.13.14.19.21l.01.01c.06.07.12.14.18.21l.01.01c.06.07.12.14.18.22l.01.01c.05.07.11.14.17.22 0 0 .01.01.01.01.05.08.11.15.16.23l.01.01.15.23.01.01c.05.08.1.16.15.24 0 .01.01.01.01.02.05.08.09.16.14.24l.01.01c.04.08.09.17.13.25l.01.02c.04.08.08.17.12.25l.01.01c.04.08.08.17.12.26l.01.01c.04.08.07.17.11.25v.01c.04.08.07.17.11.26v.01c.03.08.07.17.1.25v.01c.03.09.06.17.1.26v.01c.03.09.06.17.09.26v.01l.09.27v.01c.03.09.05.18.08.27v.01c.03.09.05.18.08.27v.01c.02.09.05.18.07.27v.01c.02.09.04.19.07.28v.01c.02.09.04.19.06.28v.01c.02.09.04.19.06.28v.01c.02.1.03.19.05.29v.01c.02.1.03.19.05.29v.01l.04.29v.01c.01.1.02.2.04.29v.02c.01.1.02.19.03.29v.02c.01.1.01.19.02.29v.02c.01.1 0 .19.01.29v.06c0 .09 0 .18.01.27v.07l.01.28v.05l.01.27v.06c0 .08.01.17.01.25v.06c0 .08.01.17.01.26v.07c0 .08.01.16.01.24 0 .03 0 .06.01.09 0 .08.01.16.01.24l.01.07c0 .08.01.16.01.23l.01.08c0 .08.01.16.01.23l.01.08c0 .08.01.15.01.22l.01.08c0 .08 0 .15.01.22l.01.08c0 .08 0 .15.01.22l.01.08c0 .08 0 .15.01.21l.01.08c0 .07 0 .15.01.21l.01.08c0 .07 0 .14.01.21l.01.08c0 .07 0 .14 0 .21l.01.07c0 .07 0 .14 0 .21l.01.07c0 .07 0 .14 0 .21v.07c0 .07 0 .13 0 .2v.08c0 .07 0 .13 0 .2v.07c0 .07 0 .13 0 .2v.07c0 .06 0 .13 0 .19v.07c0 .06 0 .13 0 .19v.06c0 .06 0 .13 0 .19v.06c0 .06 0 .12 0 .19v.06c0 .06-.01.12-.01.18v.06c0 .06-.01.12-.01.18v.06c0 .06-.01.12-.01.18v.05c0 .06-.01.12-.02.17v.05c0 .06-.01.12-.02.17v.05c0 .06-.01.11-.02.17v.05c0 .06-.01.11-.02.16v.05c-.01.05-.01.11-.02.16v.04c-.01.05-.01.11-.02.16v.04c-.01.05-.02.11-.03.16v.04c-.01.05-.02.1-.03.16v.04c-.01.05-.02.1-.03.15v.04c-.01.05-.02.1-.03.15v.03c-.01.05-.02.1-.04.15v.03c-.01.05-.02.1-.04.14v.03c-.01.05-.02.09-.04.14v.03c-.02.05-.03.09-.04.14v.03c-.02.04-.03.09-.05.13v.03c-.02.04-.03.09-.05.13v.03c-.02.04-.03.08-.05.13v.02c-.02.04-.03.08-.05.13v.02c-.02.04-.04.08-.06.12v.02c-.02.04-.04.08-.06.12v.02c-.02.04-.04.08-.06.12v.02c-.02.04-.04.08-.07.11v.02c-.02.04-.05.07-.07.11v.02c-.02.03-.05.07-.07.11v.01c-.03.03-.05.07-.08.1v.01c-.03.03-.05.07-.08.1v.01c-.03.03-.05.06-.08.1v.01c-.03.03-.06.06-.09.09v.01c-.03.03-.06.06-.09.09v.01c-.03.03-.06.06-.09.09-.03.03-.06.06-.1.08l-.01.01c-.03.03-.07.05-.1.08l-.01.01c-.03.03-.07.05-.1.08h-.01c-.04.02-.07.05-.11.07h-.01c-.04.02-.07.04-.11.07h-.01c-.04.02-.08.04-.11.06h-.01c-.04.02-.08.04-.12.06h-.01c-.04.02-.08.03-.12.05h-.01c-.04.02-.08.03-.13.05h-.01c-.04.01-.09.03-.13.04h-.01c-.05.01-.09.02-.14.04h-.01c-.05.01-.09.02-.14.03h-.01c-.05.01-.1.02-.14.03h-.02c-.05.01-.1.01-.15.02h-.02c-.05 0-.1.01-.15.02h-.02c-.05 0-.11 0-.16.01h-.02c-.05 0-.11 0-.16.01h-.04c-.05 0-.11 0-.17 0h-.07c-.05 0-.11 0-.17 0h-.34c-.06 0-.11 0-.17 0h-.09c-.05 0-.11-.01-.16-.01h-.03c-.05 0-.11-.01-.16-.02h-.02c-.05 0-.1-.01-.16-.02h-.01c-.05-.01-.11-.01-.16-.02h-.01c-.05-.01-.11-.02-.16-.03h-.01c-.05-.01-.11-.02-.16-.03 0 0-.01 0-.01-.01-.05-.01-.11-.03-.16-.04 0 0 0 0-.01 0-.05-.02-.11-.03-.16-.05 0 0 0 0-.01-.01-.05-.02-.11-.04-.16-.06 0 0 0 0-.01 0-.05-.02-.11-.04-.16-.07h-.01c-.05-.02-.1-.05-.16-.08 0 0 0 0-.01-.01-.05-.03-.1-.06-.15-.09 0 0 0 0-.01-.01-.05-.03-.1-.06-.15-.1 0 0 0-.01-.01-.01-.05-.03-.09-.07-.14-.11 0 0 0-.01-.01-.01-.04-.04-.09-.08-.13-.12 0 0 0-.01-.01-.01-.04-.04-.08-.09-.12-.14 0 0 0-.01-.01-.01-.04-.05-.08-.1-.11-.15 0 0 0-.01-.01-.01-.03-.05-.07-.11-.1-.17v-.01c-.03-.06-.06-.12-.09-.18v-.01c-.03-.06-.05-.13-.07-.19v-.01c-.02-.07-.04-.14-.06-.21v-.01c-.02-.07-.03-.15-.04-.23v-.01c-.01-.08-.02-.16-.02-.24v-.02c0-.08-.01-.17-.01-.25v-.05c0-.08 0-.17 0-.25v-.06c0-.08.01-.16.01-.24v-.07c.01-.08.01-.15.02-.23l.01-.07c.01-.08.01-.15.02-.22l.01-.07c.01-.07.02-.15.03-.22l.01-.06c.01-.07.02-.15.04-.22l.01-.06c.01-.07.03-.14.04-.21l.01-.06c.02-.07.03-.14.05-.21l.01-.05c.02-.07.04-.13.05-.2l.01-.05c.02-.07.04-.13.06-.19l.02-.05c.02-.06.04-.13.07-.19l.02-.04c.02-.06.05-.12.07-.18l.02-.04c.03-.06.05-.12.08-.17l.02-.04c.03-.06.05-.11.08-.17l.02-.03c.03-.05.06-.11.09-.16l.02-.03c.03-.05.06-.1.09-.15l.02-.03c.03-.05.06-.1.1-.14l.03-.03c.03-.05.07-.09.1-.14l.03-.03.11-.13.03-.03c.04-.04.07-.09.11-.13l.03-.03.12-.12.04-.03c.04-.04.08-.08.13-.11l.04-.03c.04-.03.09-.07.13-.11l.05-.03c.04-.03.09-.07.14-.1l.05-.03c.05-.03.1-.06.15-.09l.05-.02c.05-.03.11-.05.16-.08l.06-.02c.05-.02.11-.05.17-.07l.07-.02c.05-.02.12-.04.18-.06l.08-.01c.06-.02.12-.03.19-.05l.09-.01c.06-.01.13-.02.2-.03l.11-.01c.06-.01.13-.01.2-.01h.13c.07 0 .14 0 .21.01h.13c.07.01.14.02.2.03l.09.01c.07.02.13.03.19.05l.08.01c.06.02.12.04.18.06l.07.02c.06.02.11.05.17.07l.06.02c.05.02.11.05.16.08l.05.02c.05.03.1.06.15.09l.05.03c.05.03.09.07.14.1l.05.03c.04.04.09.07.13.11l.04.03c.04.04.08.08.13.11l.04.03c.04.04.08.08.12.12l.03.03c.04.04.07.09.11.13l.03.03.11.13.03.03c.03.05.07.09.1.14l.03.03c.03.05.06.1.1.14l.02.03c.03.05.06.1.09.15l.02.03c.03.05.06.11.09.16l.02.03c.03.06.05.11.08.17l.02.04c.03.06.05.11.08.17l.02.04c.02.06.05.12.07.18l.02.04c.02.06.05.13.07.19l.02.05c.02.06.04.13.06.19l.01.05c.02.07.04.13.05.2l.01.05c.02.07.03.14.05.21l.01.06c.01.07.03.14.04.21l.01.06c.01.07.02.15.04.22l.01.06c.01.07.02.15.03.22l.01.07c.01.08.01.15.02.23v.07c.01.08.01.16.01.24v.06c0 .08 0 .17 0 .25v.05c0 .08-.01.17-.01.25v.02c-.01.08-.01.16-.02.24v.01c-.01.08-.02.15-.04.23v.01c-.02.07-.04.14-.06.21v.01c-.02.07-.05.13-.07.19v.01c-.03.06-.06.12-.09.18v.01c-.03.06-.07.11-.1.17 0 0 0 .01-.01.01-.04.05-.08.1-.11.15 0 0 0 .01-.01.01-.04.05-.08.09-.12.14 0 0 0 .01-.01.01-.04.04-.09.08-.13.12 0 0 0 .01-.01.01-.05.04-.09.07-.14.11 0 0 0 .01-.01.01-.05.03-.1.07-.15.1 0 0 0 0-.01.01-.05.03-.1.06-.15.09 0 0 0 0-.01.01-.05.02-.1.05-.16.08h-.01c-.05.02-.11.04-.16.07 0 0 0 0-.01 0-.05.02-.11.04-.16.06 0 0 0 0-.01.01-.05.02-.11.03-.16.05 0 0 0 0-.01 0-.05.01-.11.03-.16.04 0 0-.01 0-.01.01-.05.01-.11.02-.16.03h-.01c-.05.01-.11.02-.16.03h-.01c-.05.01-.11.01-.16.02h-.01c-.05.01-.11.01-.16.02h-.02c-.05.01-.1.01-.16.02h-.03c-.05 0-.11.01-.16.01h-.09c-.05 0-.11 0-.17 0H8.88c-.06 0-.11 0-.17 0h-.07c-.05 0-.11 0-.17 0h-.04c-.05 0-.11 0-.16-.01h-.02c-.05 0-.11 0-.16-.01h-.02c-.05 0-.1-.01-.15-.02h-.02c-.05-.01-.1-.01-.15-.02h-.02c-.05-.01-.1-.02-.14-.03h-.01c-.05-.01-.09-.02-.14-.03h-.01c-.05-.01-.09-.02-.14-.04h-.01c-.04-.01-.09-.03-.13-.04h-.01c-.04-.02-.08-.03-.13-.05h-.01c-.04-.02-.08-.03-.12-.05h-.01c-.04-.02-.08-.04-.12-.06h-.01c-.04-.02-.08-.04-.11-.06h-.01c-.04-.02-.07-.04-.11-.07h-.01c-.04-.02-.07-.05-.11-.07h-.01c-.03-.03-.07-.05-.1-.08l-.01-.01c-.03-.03-.07-.05-.1-.08l-.01-.01c-.03-.03-.06-.06-.1-.08-.03-.03-.06-.06-.09-.09v-.01c-.03-.03-.06-.06-.09-.09v-.01c-.03-.03-.06-.06-.09-.09v-.01c-.03-.03-.05-.06-.08-.1v-.01c-.03-.03-.05-.07-.08-.1v-.01c-.03-.03-.05-.07-.08-.1v-.01c-.02-.04-.05-.07-.07-.11v-.02c-.02-.04-.05-.07-.07-.11v-.02c-.02-.04-.04-.08-.07-.11v-.02c-.02-.04-.04-.08-.06-.12v-.02c-.02-.04-.04-.08-.06-.12v-.02c-.02-.04-.04-.08-.06-.12v-.02c-.02-.04-.03-.09-.05-.13v-.02c-.02-.04-.03-.08-.05-.13v-.03c-.02-.04-.03-.09-.05-.13v-.03c-.01-.04-.03-.09-.04-.14v-.03c-.01-.05-.02-.09-.04-.14v-.03c-.01-.05-.02-.1-.04-.14v-.03c-.01-.05-.02-.1-.03-.15v-.04c-.01-.05-.02-.1-.03-.15v-.04c-.01-.05-.02-.1-.03-.16v-.04c-.01-.05-.02-.11-.03-.16v-.04c-.01-.05-.01-.11-.02-.16v-.04c-.01-.05-.01-.11-.02-.16v-.05c-.01-.05-.01-.11-.02-.16v-.05c0-.06-.01-.11-.02-.17v-.05c0-.06-.01-.12-.02-.17v-.05c0-.06-.01-.12-.01-.18v-.06c0-.06-.01-.12-.01-.18v-.06c0-.06-.01-.12-.01-.18v-.06c0-.06 0-.12 0-.19v-.06c0-.06 0-.13 0-.19v-.06c0-.06 0-.13 0-.19v-.07c0-.07 0-.13 0-.2v-.07c0-.07 0-.13 0-.2v-.08c0-.07 0-.13 0-.2v-.07c0-.07 0-.14 0-.21v-.07c0-.07 0-.14 0-.21l-.01-.08c0-.07 0-.14-.01-.21l-.01-.08c0-.07 0-.15-.01-.21l-.01-.08c0-.08 0-.15-.01-.22l-.01-.08c0-.08 0-.15-.01-.22l-.01-.08c0-.08-.01-.15-.01-.23l-.01-.08c0-.08-.01-.15-.01-.23l-.01-.07c-.01-.08-.01-.16-.01-.24-.01-.03-.01-.06-.01-.09-.01-.08-.01-.16-.01-.24v-.07c-.01-.08-.01-.17-.01-.26v-.06c-.01-.08-.01-.17-.01-.25v-.06l-.01-.27v-.05l-.01-.28v-.07c0-.09 0-.18-.01-.27v-.06c0-.1-.01-.19-.01-.29v-.02c-.01-.1-.01-.19-.02-.29v-.02c-.01-.1-.02-.19-.03-.29v-.02c-.01-.1-.02-.2-.04-.29v-.01l-.04-.29v-.01c-.02-.1-.03-.19-.05-.29v-.01c-.02-.1-.03-.19-.05-.29v-.01c-.02-.09-.04-.19-.06-.28v-.01c-.02-.09-.04-.19-.06-.28v-.01c-.02-.09-.04-.19-.07-.28v-.01c-.02-.09-.05-.18-.07-.27v-.01c-.03-.09-.05-.18-.08-.27v-.01c-.03-.09-.05-.18-.08-.27v-.01l-.09-.27v-.01c-.03-.09-.06-.17-.09-.26v-.01c-.03-.09-.06-.17-.1-.26v-.01c-.03-.08-.07-.17-.1-.25v-.01c-.04-.08-.07-.17-.11-.26v-.01c-.04-.08-.07-.17-.11-.25l-.01-.01c-.04-.08-.08-.17-.12-.26l-.01-.01c-.04-.08-.09-.17-.13-.25l-.01-.02c-.05-.08-.09-.16-.14-.24-.01-.01-.01-.01-.01-.02-.05-.08-.1-.16-.15-.24l-.01-.01-.15-.23-.01-.01c-.05-.08-.11-.15-.16-.23-.01 0-.01-.01-.01-.01-.06-.07-.11-.15-.17-.22l-.01-.01c-.06-.07-.12-.14-.18-.22l-.01-.01c-.06-.07-.12-.14-.18-.21l-.01-.01c-.06-.07-.13-.14-.19-.21l-.01-.01c-.06-.07-.13-.13-.19-.2l-.01-.01c-.07-.07-.13-.13-.2-.2l-.01-.01c-.07-.06-.14-.13-.21-.19l-.01-.01c-.07-.06-.14-.13-.21-.19l-.01-.01c-.07-.06-.15-.12-.22-.19-.01 0-.01-.01-.01-.01-.08-.06-.16-.13-.23-.19l-.01-.01c-.08-.06-.16-.12-.24-.18-.01 0-.01-.01-.01-.01-.09-.06-.17-.12-.25-.18h-.01c-.09-.05-.18-.11-.27-.16h-.01c-.09-.05-.18-.11-.27-.16h-.01c-.08-.04-.16-.08-.24-.12l-.13-.07-.31-1.38-1.35-1.75a9.3 9.3 0 0 0-3.69.46 5.1 5.1 0 0 0-3.05 2.93L0 7.5v1.02h.01c.06 1.38.46 2.7 1.17 3.82l.64 1.02c.53.82 1.2 1.48 1.97 1.93.77.45 1.64.68 2.53.68.89 0 1.76-.23 2.53-.68.77-.45 1.44-1.11 1.97-1.93l.64-1.02c.71-1.12 1.11-2.44 1.17-3.82h.01V7.5l-.41-2.03z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Cash App</h3>
                </div>

                <div>
                    <label for="cashapp_tag" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Cash App Tag (Cashtag)
                    </label>
                    <input wire:model="cashapp_tag"
                           type="text"
                           id="cashapp_tag"
                           class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                           placeholder="$YourCashTag">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your Cash App username starting with $ (e.g., $JohnDoe123)</p>
                    @error('cashapp_tag')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Venmo Section -->
            <div class="space-y-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.38 2.99a8.28 8.28 0 0 1 .18 5.88L15.1 21.01H9.79L4.62 2.99h5.36l2.89 11.93 1.81-11.93h5.36-.01-.29zM19.09 2.99z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Venmo</h3>
                </div>

                <div>
                    <label for="venmo_username" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Venmo Username
                    </label>
                    <input wire:model="venmo_username"
                           type="text"
                           id="venmo_username"
                           class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                           placeholder="@YourVenmoUsername">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your Venmo username with or without @ (e.g., @JohnDoe or JohnDoe)</p>
                    @error('venmo_username')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Square Integration Section -->
            <div class="space-y-4 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                        <!-- Square Logo -->
                        <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4.01 8.54C4.01 6.58 5.61 5 7.56 5c1.96 0 3.55 1.58 3.55 3.54 0 1.95-1.59 3.54-3.55 3.54-1.95 0-3.55-1.59-3.55-3.54zm8.44 0c0-1.96 1.59-3.54 3.55-3.54 1.95 0 3.54 1.58 3.54 3.54 0 1.95-1.59 3.54-3.54 3.54-1.96 0-3.55-1.59-3.55-3.54zM4.01 16c0-1.96 1.59-3.55 3.55-3.55 1.96 0 3.55 1.59 3.55 3.55 0 1.95-1.59 3.54-3.55 3.54-1.95 0-3.55-1.59-3.55-3.54zm8.44 0c0-1.96 1.59-3.55 3.55-3.55 1.95 0 3.54 1.59 3.54 3.55 0 1.95-1.59 3.54-3.54 3.54-1.96 0-3.55-1.59-3.55-3.54z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Square Integration (Beta)</h3>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-4">
                            Connect your Square account to enable automatic recurring payments for your groups. Members will be charged automatically each billing cycle via credit/debit card.
                        </p>

                        @if(Auth::user()->hasSquareConnected())
                            <!-- Connected State -->
                            <div class="flex items-center justify-between p-4 bg-white dark:bg-zinc-800 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">Square Connected</p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            Merchant ID: {{ Auth::user()->square_merchant_id }}
                                            @if(Auth::user()->square_connected_at)
                                                • Connected {{ Auth::user()->square_connected_at->diffForHumans() }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('square.disconnect') }}" onsubmit="return confirm('Are you sure you want to disconnect Square? This will not affect existing subscriptions.');">
                                    @csrf
                                    <flux:button variant="danger" size="sm" type="submit">
                                        Disconnect
                                    </flux:button>
                                </form>
                            </div>
                        @else
                            <!-- Not Connected State -->
                            <div class="flex items-start gap-3 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 mb-4">
                                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                    <p class="font-medium mb-1">Benefits of Square Integration:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>Automatic recurring payments for group members</li>
                                        <li>Accept credit and debit cards</li>
                                        <li>Real-time payment tracking and status updates</li>
                                        <li>Reduce manual payment collection work</li>
                                        <li>Professional invoicing and receipts</li>
                                    </ul>
                                </div>
                            </div>

                            <a href="{{ route('square.connect') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.01 8.54C4.01 6.58 5.61 5 7.56 5c1.96 0 3.55 1.58 3.55 3.54 0 1.95-1.59 3.54-3.55 3.54-1.95 0-3.55-1.59-3.55-3.54zm8.44 0c0-1.96 1.59-3.54 3.55-3.54 1.95 0 3.54 1.58 3.54 3.54 0 1.95-1.59 3.54-3.54 3.54-1.96 0-3.55-1.59-3.55-3.54zM4.01 16c0-1.96 1.59-3.55 3.55-3.55 1.96 0 3.55 1.59 3.55 3.55 0 1.95-1.59 3.54-3.55 3.54-1.95 0-3.55-1.59-3.55-3.54zm8.44 0c0-1.96 1.59-3.55 3.55-3.55 1.95 0 3.54 1.59 3.54 3.55 0 1.95-1.59 3.54-3.54 3.54-1.96 0-3.55-1.59-3.55-3.54z"/>
                                </svg>
                                Connect Square Account
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full bg-teal-600 hover:bg-teal-500" data-test="update-payment-accounts-button">
                        {{ __('Save Payment Accounts') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="payment-accounts-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
