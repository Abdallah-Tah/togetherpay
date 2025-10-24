@php($heading = $pageHeading ?? ($group->name ?? 'Group'))

<div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $heading }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review the latest performance and member activity for this group.</p>
        </div>
        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Members</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $group->members->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($group->total_amount, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Billing</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ ucfirst(strtolower($group->cadence)) }}</p>
                    </div>
                </div>
            </div>

            @if($currentCycle)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Collection Rate</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ round($currentCycle->collection_rate) }}%</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">No active cycle</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Tabs -->
        <div x-data="{ activeTab: 'members' }" class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'members'"
                            :class="activeTab === 'members' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition">
                        Members ({{ $group->members->count() }})
                    </button>
                    <button @click="activeTab = 'cycles'"
                            :class="activeTab === 'cycles' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition">
                        Billing Cycles
                    </button>
                    <button @click="activeTab = 'settings'"
                            :class="activeTab === 'settings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
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
                        <div class="space-y-4">
                            @foreach($group->members as $member)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $member->user->initials() }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $member->user->name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $member->user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                @if($member->share_type === 'PERCENT')
                                                    {{ $member->share_value }}%
                                                @else
                                                    ${{ number_format($member->share_value, 2) }}
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($member->preferred_channel) }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                            {{ ucfirst($member->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            <button type="button"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Member
                            </button>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No members yet</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first member</p>
                            <div class="mt-6">
                                <button type="button"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Member
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Cycles Tab -->
                <div x-show="activeTab === 'cycles'" x-cloak>
                    @if($group->cycles->count() > 0)
                        <div class="space-y-4">
                            @foreach($group->cycles as $cycle)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ $cycle->period_start->format('M d') }} - {{ $cycle->period_end->format('M d, Y') }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Due {{ $cycle->due_at->format('M d, Y') }}
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            {{ $cycle->status === 'collecting' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                               ($cycle->status === 'closed' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                            {{ ucfirst($cycle->status) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-700 dark:text-gray-300">
                                            ${{ number_format($cycle->collected_amount, 2) }} of ${{ number_format($cycle->total_amount, 2) }}
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ round($cycle->collection_rate) }}%</span>
                                    </div>
                                    <div class="mt-2 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $cycle->collection_rate }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No billing cycles yet</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cycles will be created automatically based on your schedule</p>
                        </div>
                    @endif
                </div>

                <!-- Settings Tab -->
                <div x-show="activeTab === 'settings'" x-cloak>
                    <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Billing Frequency</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ ucfirst(strtolower($group->cadence)) }}</dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Due Day</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">Day {{ $group->due_day }} of each month</dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Grace Period</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $group->grace_days }} days</dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timezone</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $group->timezone }}</dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Currency</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $group->currency }}</dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1 sm:mt-0 sm:col-span-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $group->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ ucfirst($group->status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
</div>
