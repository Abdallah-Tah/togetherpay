@php
    $pageTitle = $title ?? 'Create Group';
@endphp

<div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set up a new bill-splitting group and invite members in minutes.</p>
        </div>

        <div class="max-w-3xl">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <form wire:submit="save">
                <div class="p-6 space-y-6">
                    <!-- Group Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Group Name <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name"
                               type="text"
                               id="name"
                               class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="e.g., Family Phone Plan">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total Amount -->
                    <div>
                        <label for="total_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
                                   class="block w-full pl-7 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="0.00">
                        </div>
                        @error('total_amount')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The total monthly bill amount to split among members</p>
                    </div>

                    <!-- Billing Cadence -->
                    <div>
                        <label for="cadence" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Billing Frequency <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="cadence"
                                id="cadence"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                            <label for="due_day" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Due Day of Month <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="due_day"
                                   type="number"
                                   min="1"
                                   max="28"
                                   id="due_day"
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('due_day')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Day 1-28 of each month</p>
                        </div>

                        <!-- Grace Period -->
                        <div>
                            <label for="grace_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Grace Period (days) <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="grace_days"
                                   type="number"
                                   min="0"
                                   max="30"
                                   id="grace_days"
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('grace_days')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Days after due date before marking late</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Timezone -->
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Timezone
                            </label>
                            <select wire:model="timezone"
                                    id="timezone"
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Currency
                            </label>
                            <select wire:model="currency"
                                    id="currency"
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="CAD">CAD ($)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">What happens next?</h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
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
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between rounded-b-lg">
                    <a href="{{ route('app.groups.index') }}"
                       wire:navigate
                       class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
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
