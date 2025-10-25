@php
    $pageTitle = $title ?? 'Groups';
@endphp

<div class="space-y-8">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-3xl shadow-xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Your Groups</h1>
                <p class="text-brand-100 text-lg">Manage and monitor all your bill-splitting groups</p>
            </div>
            <div class="hidden md:block">
                <a href="{{ route('app.groups.create') }}" wire:navigate
                   class="inline-flex items-center px-6 py-3 bg-white text-brand-700 font-semibold rounded-2xl hover:bg-brand-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Group
                </a>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 p-6">
        <div class="relative max-w-xl">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.live="search"
                   type="search"
                   placeholder="Search groups by name..."
                   class="block w-full pl-11 pr-4 py-3.5 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-shadow">
        </div>
    </div>

    <!-- Groups Grid -->
    @if($groups->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($groups as $group)
                <a href="{{ route('app.groups.show', $group) }}" wire:navigate
                   class="group bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-zinc-100 dark:border-zinc-700 transform hover:-translate-y-1">

                    <!-- Group Header with Icon -->
                    <div class="p-6 border-b border-zinc-100 dark:border-zinc-700">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        {{ strtoupper(substr($group->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                                        {{ $group->name }}
                                    </h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}
                                    </p>
                                </div>
                            </div>
                            <span class="flex-shrink-0 px-3 py-1 text-xs font-semibold rounded-full {{ $group->status === 'active' ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                {{ ucfirst($group->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Group Details -->
                    <div class="p-6 space-y-4">
                        <!-- Amount -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="p-2.5 bg-brand-50 dark:bg-brand-900/20 rounded-lg">
                                    <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Amount</p>
                                    <p class="text-base font-semibold text-zinc-900 dark:text-white">${{ number_format($group->total_amount, 2) }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-700 px-3 py-1.5 rounded-full">
                                {{ ucfirst(strtolower(str_replace('_', ' ', $group->cadence))) }}
                            </span>
                        </div>

                        <!-- Billing Info -->
                        <div class="flex items-center text-sm text-zinc-600 dark:text-zinc-400">
                            <svg class="h-4 w-4 mr-2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Due day {{ $group->due_day }} • {{ $group->grace_days }} day grace</span>
                        </div>

                        @if($group->currentCycle())
                            @php $cycle = $group->currentCycle(); @endphp
                            <!-- Collection Progress -->
                            <div class="pt-4 border-t border-zinc-100 dark:border-zinc-700">
                                <div class="flex items-center justify-between text-sm mb-2">
                                    <span class="text-zinc-600 dark:text-zinc-400 font-medium">Collection Progress</span>
                                    <span class="font-bold text-brand-600 dark:text-brand-400">
                                        {{ round($cycle->collection_rate) }}%
                                    </span>
                                </div>
                                <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-brand-500 to-brand-600 h-2.5 rounded-full transition-all duration-500 ease-out shadow-sm"
                                         style="width: {{ $cycle->collection_rate }}%"></div>
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                                    ${{ number_format($cycle->collected_amount_cents / 100, 2) }} of ${{ number_format($group->total_amount, 2) }} collected
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                Created {{ $group->created_at->diffForHumans() }}
                            </span>
                            <span class="text-sm text-brand-600 dark:text-brand-400 font-medium group-hover:underline">
                                View Details →
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700">
            <div class="px-6 py-16 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700 mb-4">
                    <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">
                    @if($search)
                        No groups found
                    @else
                        No groups yet
                    @endif
                </h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4 max-w-md mx-auto">
                    @if($search)
                        We couldn't find any groups matching "{{ $search }}". Try adjusting your search.
                    @else
                        Get started by creating your first bill-splitting group
                    @endif
                </p>
                @if(!$search)
                    <a href="{{ route('app.groups.create') }}" wire:navigate
                       class="inline-flex items-center px-5 py-2.5 bg-brand-600 text-white font-semibold rounded-xl hover:bg-brand-700 transition-colors duration-200 shadow-sm">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create your first group
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
