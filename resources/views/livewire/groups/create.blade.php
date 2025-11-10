<div>
    @php
        $pageTitle = $title ?? 'Create Group';
    @endphp

    <div class="space-y-8">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 rounded-3xl shadow-xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $pageTitle }}</h1>
                <p class="text-white/90 text-lg">Set up a new bill-splitting group and invite members in minutes.</p>
            </div>
        </div>
    </div>

    <div class="max-w-3xl">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700">
            <form wire:submit="save">
                <div class="p-6 space-y-6">
                    <!-- Group Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Group Name <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name"
                               type="text"
                               id="name"
                               class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                               placeholder="e.g., Family Phone Plan">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total Amount -->
                    <div>
                        <label for="total_amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Total Bill Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">$</span>
                            </div>
                            <input wire:model="total_amount"
                                   type="number"
                                   step="0.01"
                                   id="total_amount"
                                   class="block w-full pl-7 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                                   placeholder="0.00">
                        </div>
                        @error('total_amount')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The total monthly bill amount to split among members</p>
                    </div>

                    <!-- Billing Mode -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-4">
                            How do you want to collect money? <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-3">
                            <!-- Manual Mode -->
                            <label class="flex items-start p-4 rounded-xl border-2 cursor-pointer transition" :class="@js($billing_mode === 'manual') ? 'border-teal-500 bg-teal-50 dark:border-teal-400 dark:bg-teal-950/20' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:border-teal-300 dark:hover:border-teal-700'">
                                <input wire:model="billing_mode"
                                       type="radio"
                                       value="manual"
                                       class="mt-1 h-5 w-5 text-teal-600 focus:ring-teal-500 cursor-pointer">
                                <div class="ml-3 flex-1">
                                    <p class="font-semibold text-zinc-900 dark:text-white">Manual Collection</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Send payment links, members confirm payment, you approve</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">⏱️ Requires manual coordination but gives you full control</p>
                                </div>
                            </label>

                            <!-- Auto-Square Mode -->
                            <label class="flex items-start p-4 rounded-xl border-2 cursor-pointer transition" :class="@js($billing_mode === 'auto_square') ? 'border-teal-500 bg-teal-50 dark:border-teal-400 dark:bg-teal-950/20' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:border-teal-300 dark:hover:border-teal-700'">
                                <input wire:model="billing_mode"
                                       type="radio"
                                       value="auto_square"
                                       class="mt-1 h-5 w-5 text-teal-600 focus:ring-teal-500 cursor-pointer">
                                <div class="ml-3 flex-1">
                                    <p class="font-semibold text-zinc-900 dark:text-white">Automatic (Square)</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Square charges members automatically on due date via Cash App</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">🤖 Fully automated, no manual coordination needed</p>
                                </div>
                            </label>
                        </div>
                        @error('billing_mode')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Auto-Square Warning -->
                    @if ($billing_mode === 'auto_square')
                        @if (auth()->user()->isSquareConfigured())
                            <div class="rounded-xl bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-900/40">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Square Connected</h3>
                                        <p class="mt-1 text-sm text-green-700 dark:text-green-300">Your Square account is connected and ready for automatic payments!</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl bg-yellow-50 dark:bg-yellow-900/20 p-4 border border-yellow-200 dark:border-yellow-900/40">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Square Required</h3>
                                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">You need to connect your Square account first. You can do this in <a href="{{ route('square-account.edit') }}" class="underline font-medium hover:no-underline">Settings → Square Account</a>.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Billing Cadence -->
                    <div>
                        <label for="cadence" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Billing Frequency <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="cadence"
                                id="cadence"
                                class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400">
                            <option value="MONTHLY">Monthly (on a specific day)</option>
                            <option value="EVERY_4_WEEKS">Every 4 Weeks</option>
                        </select>
                        @error('cadence')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Due Day -->
                        <div>
                            <label for="due_day" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Due Day of Month <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="due_day"
                                   type="number"
                                   min="1"
                                   max="28"
                                   id="due_day"
                                   class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400">
                            @error('due_day')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Day 1-28 of each month</p>
                        </div>

                        <!-- Grace Period -->
                        <div>
                            <label for="grace_days" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Grace Period (days) <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="grace_days"
                                   type="number"
                                   min="0"
                                   max="30"
                                   id="grace_days"
                                   class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400">
                            @error('grace_days')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Days after due date before marking late</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Timezone -->
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Timezone
                            </label>
                            <select wire:model="timezone"
                                    id="timezone"
                                    class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400">
                                <option value="America/New_York">Eastern Time</option>
                                <option value="America/Chicago">Central Time</option>
                                <option value="America/Denver">Mountain Time</option>
                                <option value="America/Los_Angeles">Pacific Time</option>
                                <option value="America/Anchorage">Alaska Time</option>
                                <option value="Pacific/Honolulu">Hawaii Time</option>
                            </select>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Currency
                            </label>
                            <select wire:model="currency"
                                    id="currency"
                                    class="block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="CAD">CAD ($)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="rounded-xl bg-teal-50 dark:bg-teal-900/20 p-4 border border-teal-200 dark:border-teal-900/40">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-teal-800 dark:text-teal-200">What happens next?</h3>
                                <div class="mt-2 text-sm text-teal-700 dark:text-teal-300">
                                    <p>After creating your group, you'll be able to:</p>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        <li>Add members and set their payment shares</li>
                                        <li>Configure payment methods (Venmo, Cash App)</li>
                                        <li>Automatic billing cycles will start based on your schedule</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-700/50 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-b-2xl">
                    <a href="{{ route('app.groups.index') }}"
                       wire:navigate
                       class="text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-400 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg">
                        <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="save">Create Group</span>
                        <span wire:loading wire:target="save">Creating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>
