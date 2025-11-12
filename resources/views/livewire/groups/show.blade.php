<div>
    @php
        $pageTitle = $group->name;
    @endphp

    <div class="space-y-8">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-3xl shadow-xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $pageTitle }}</h1>
                <p class="text-brand-100 text-lg">Review the latest performance and member activity for this group.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('app.groups.invoices', $group) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Invoices
                </a>
                <a href="{{ route('app.groups.settings', $group) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Members -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-200 p-6 border border-zinc-100 dark:border-zinc-700">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Members</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ $group->members->count() }}</p>
                </div>
                <div class="p-3 bg-brand-50 dark:bg-brand-900/20 rounded-xl">
                    <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-200 p-6 border border-zinc-100 dark:border-zinc-700">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Amount</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">${{ number_format($group->total_amount, 2) }}</p>
                </div>
                <div class="p-3 bg-success-50 dark:bg-success-900/20 rounded-xl">
                    <svg class="h-6 w-6 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Billing -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-200 p-6 border border-zinc-100 dark:border-zinc-700">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Billing</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ ucfirst(strtolower($group->cadence)) }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Collection Rate / Status -->
        @if($currentCycle)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-200 p-6 border border-zinc-100 dark:border-zinc-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Collection Rate</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ round($currentCycle->collection_rate) }}%</p>
                    </div>
                    <div class="p-3 bg-warning-50 dark:bg-warning-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-200 p-6 border border-zinc-100 dark:border-zinc-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Status</p>
                        <p class="text-base font-semibold text-zinc-900 dark:text-white mb-2">No active cycle</p>
                    </div>
                    <div class="p-3 bg-zinc-100 dark:bg-zinc-700 rounded-xl">
                        <svg class="h-6 w-6 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Tabs -->
    <div x-data="{ activeTab: 'members' }" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
        <!-- Tab Navigation -->
        <div class="border-b border-zinc-100 dark:border-zinc-700">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'members'"
                        :class="activeTab === 'members' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    Members ({{ $group->members->count() }})
                </button>
                <button @click="activeTab = 'cycles'"
                        :class="activeTab === 'cycles' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    Billing Cycles
                </button>
                <button @click="activeTab = 'settings'"
                        :class="activeTab === 'settings' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    Settings
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Members Tab -->
            <div x-show="activeTab === 'members'" x-cloak>
                @if($group->members->count() > 0)
                    <div class="space-y-3">
                        @foreach($group->members as $member)
                            <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition border border-zinc-100 dark:border-zinc-700">
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-sm font-semibold">
                                        {{ $member->user->initials() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-zinc-900 dark:text-white truncate">{{ $member->user->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 hidden sm:block truncate">{{ $member->user->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 sm:space-x-4 flex-shrink-0">
                                    <div class="text-right">
                                        <p class="font-semibold text-zinc-900 dark:text-white text-sm sm:text-base">
                                            @if($member->share_type === 'PERCENT')
                                                {{ $member->share_value }}%
                                            @else
                                                ${{ number_format($member->share_value, 2) }}
                                            @endif
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase hidden sm:block">{{ ucfirst($member->preferred_channel) }}</p>
                                    </div>
                                    <span class="px-2 sm:px-3 py-1 text-xs font-semibold rounded-full
                                        {{ $member->status === 'active' ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <button type="button"
                                class="inline-flex items-center px-4 py-2 border border-zinc-200 dark:border-zinc-700 text-sm font-medium rounded-xl text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Member
                        </button>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700 mb-4">
                            <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">No members yet</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">Get started by adding your first member</p>
                        <button type="button"
                                class="inline-flex items-center px-5 py-2.5 text-sm font-semibold rounded-xl text-white bg-brand-600 hover:bg-brand-700 transition shadow-sm">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Member
                        </button>
                    </div>
                @endif
            </div>

            <!-- Cycles Tab -->
            <div x-show="activeTab === 'cycles'" x-cloak>
                @if($group->cycles->count() > 0)
                    <div class="space-y-4">
                        @foreach($group->cycles as $cycle)
                            <div class="p-5 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-100 dark:border-zinc-700">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $cycle->period_start->format('M d') }} - {{ $cycle->period_end->format('M d, Y') }}
                                        </p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            Due {{ $cycle->due_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        {{ $cycle->status === 'collecting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                                           ($cycle->status === 'closed' ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' : 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400') }}">
                                        {{ ucfirst($cycle->status) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm mb-2">
                                    <span class="text-zinc-700 dark:text-zinc-300">
                                        ${{ number_format($cycle->collected_amount, 2) }} of ${{ number_format($cycle->total_amount, 2) }}
                                    </span>
                                    <span class="font-semibold text-brand-600 dark:text-brand-400">{{ round($cycle->collection_rate) }}%</span>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-brand-500 to-brand-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $cycle->collection_rate }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700 mb-4">
                            <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">No billing cycles yet</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cycles will be created automatically based on your schedule</p>
                    </div>
                @endif
            </div>

            <!-- Settings Tab -->
            <div x-show="activeTab === 'settings'" x-cloak>
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Billing Frequency</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white sm:mt-0 sm:col-span-2">{{ ucfirst(strtolower($group->cadence)) }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Due Day</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white sm:mt-0 sm:col-span-2">Day {{ $group->due_day }} of each month</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Grace Period</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $group->grace_days }} days</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Timezone</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $group->timezone }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Currency</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $group->currency }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Status</dt>
                        <dd class="mt-1 sm:mt-0 sm:col-span-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                {{ $group->status === 'active' ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                {{ ucfirst($group->status) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
</div>
