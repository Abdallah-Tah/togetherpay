<div>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-3xl shadow-xl p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">{{ $group->name }}</h1>
                    <p class="text-brand-100 text-lg">Manage billing and group preferences.</p>
                </div>
                <a href="{{ route('app.groups.show', $group) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Group
                </a>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Settings Card -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-brand-50 to-brand-100 dark:from-brand-900/20 dark:to-brand-800/20 px-8 py-6 border-b border-brand-200 dark:border-brand-800">
                        <h2 class="text-2xl font-bold text-brand-900 dark:text-brand-100">Payment Collection Method</h2>
                        <p class="text-sm text-brand-700 dark:text-brand-300 mt-1">Choose how you collect payments from group members</p>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- Manual Mode Option -->
                        <label class="flex items-start p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 @if($billing_mode === 'manual') border-brand-600 bg-brand-50 dark:bg-brand-900/20 @else border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 @endif">
                            <input type="radio" wire:model="billing_mode" value="manual" class="mt-1 w-4 h-4">
                            <div class="ml-4 flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xl">📝</span>
                                    <h4 class="font-semibold text-zinc-900 dark:text-white">Manual Collection</h4>
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">Send payment links to members via Venmo/Cash App. They confirm payment and you approve.</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">✓ Full control over payments  ✓ No additional setup  ✓ Fee-free</p>
                            </div>
                        </label>

                        <!-- Auto-Square Mode Option -->
                        <label class="flex items-start p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 @if($billing_mode === 'auto_square') border-success-600 bg-success-50 dark:bg-success-900/20 @else border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 @endif">
                            <input type="radio" wire:model="billing_mode" value="auto_square" class="mt-1 w-4 h-4">
                            <div class="ml-4 flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xl">🤖</span>
                                    <h4 class="font-semibold text-zinc-900 dark:text-white">Automatic (Square)</h4>
                                    @if(!auth()->user()->hasSquareAccount())
                                        <span class="text-xs px-2 py-1 rounded-full bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-300 font-medium">Setup Required</span>
                                    @endif
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">Members are charged automatically on the due date. Zero manual coordination.</p>
                                @if(!auth()->user()->hasSquareAccount())
                                    <p class="text-xs text-warning-700 dark:text-warning-300 mt-2">⚠️ Square account connection required. <a href="{{ route('square-account.edit') }}" class="underline font-medium hover:text-warning-800">Connect now</a></p>
                                @else
                                    <p class="text-xs text-success-700 dark:text-success-300 mt-2">✓ Square connected  ✓ Automatic billing  ✓ Reduced follow-ups</p>
                                @endif
                            </div>
                        </label>

                        <!-- Errors -->
                        @if ($errors->has('billing_mode'))
                            <div class="p-4 rounded-2xl bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800">
                                <p class="text-sm text-danger-700 dark:text-danger-300">
                                    <strong>Error:</strong> {{ $errors->first('billing_mode') }}
                                </p>
                            </div>
                        @endif

                        <!-- Mode Change Warning -->
                        @if ($showModeWarning)
                            <div class="p-4 rounded-2xl bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 space-y-3">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-warning-900 dark:text-warning-100 mb-2">Payment Method Change</h4>
                                        @if ($originalBillingMode === 'auto_square' && $billing_mode === 'manual')
                                            <p class="text-sm text-warning-800 dark:text-warning-200 mb-3">
                                                Switching to Manual Collection will <strong>cancel all active Square subscriptions</strong> for this group. Members will need to be sent new payment requests.
                                            </p>
                                        @elseif ($originalBillingMode === 'manual' && $billing_mode === 'auto_square')
                                            <p class="text-sm text-warning-800 dark:text-warning-200 mb-3">
                                                Switching to Automatic billing will <strong>create new Square subscriptions</strong> for all group members. Make sure they have valid payment information.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Confirmation Buttons -->
                                <div class="flex gap-3 pt-2">
                                    <button type="button" wire:click="saveBillingMode" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-warning-600 hover:bg-warning-700 text-white font-semibold rounded-xl transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Yes, Change Method
                                    </button>
                                    <button type="button" wire:click="cancelModeChange" class="flex-1 inline-flex items-center justify-center px-4 py-2 border-2 border-warning-300 dark:border-warning-700 text-warning-700 dark:text-warning-300 font-semibold rounded-xl hover:bg-warning-50 dark:hover:bg-warning-900/20 transition-all duration-200">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @elseif ($billing_mode !== $originalBillingMode)
                            <!-- Save Button when Mode Changed but No Warning Shown -->
                            <button type="button" wire:click="saveBillingMode" class="w-full inline-flex items-center justify-center px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-2xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save Changes
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="space-y-6">
                <!-- Group Info Card -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h3 class="font-bold text-zinc-900 dark:text-white mb-4">Group Information</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Billing Cadence</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mt-1">
                                @if ($group->cadence === 'MONTHLY')
                                    Monthly
                                @elseif ($group->cadence === 'EVERY_4_WEEKS')
                                    Every 4 Weeks
                                @else
                                    {{ $group->cadence }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Due Day</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mt-1">
                                Day {{ $group->due_day }} of each period
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Grace Period</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mt-1">
                                {{ $group->grace_days }} days
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Total Amount</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mt-1">
                                {{ $group->currency }} {{ number_format($group->total_amount, 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Method Card -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h3 class="font-bold text-zinc-900 dark:text-white mb-4">Current Method</h3>
                    <div class="flex items-center space-x-3 p-3 rounded-xl @if ($originalBillingMode === 'manual') bg-brand-50 dark:bg-brand-900/20 @else bg-success-50 dark:bg-success-900/20 @endif">
                        <div class="text-2xl">
                            @if ($originalBillingMode === 'manual')
                                📝
                            @else
                                🤖
                            @endif
                        </div>
                        <div>
                            <p class="font-semibold @if ($originalBillingMode === 'manual') text-brand-900 dark:text-brand-100 @else text-success-900 dark:text-success-100 @endif">
                                @if ($originalBillingMode === 'manual')
                                    Manual Collection
                                @else
                                    Automatic (Square)
                                @endif
                            </p>
                            <p class="text-xs @if ($originalBillingMode === 'manual') text-brand-700 dark:text-brand-300 @else text-success-700 dark:text-success-300 @endif">
                                @if ($originalBillingMode === 'manual')
                                    Payment links sent to members
                                @else
                                    Charges members automatically
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Help Box -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4">
                    <div class="flex space-x-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="font-medium text-blue-900 dark:text-blue-100 text-sm mb-1">Need Help?</p>
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                Changes to payment collection method will take effect on the next billing cycle.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $pageTitle = $group->name.' - Settings';
    @endphp
</div>
