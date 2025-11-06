<x-layouts.app>
    <x-slot:title>Dashboard</x-slot:title>

    <div class="space-y-8">
        <!-- Hero Section with Animated Gradient -->
        <div class="card-gradient animate-gradient fade-in p-8 text-white" data-parallax="0.1">
            <div class="flex items-center justify-between">
                <div class="slide-in-bottom">
                    <h1 class="text-3xl font-bold mb-2 animate-float">Welcome back, {{ auth()->user()->name }}!</h1>
                    <p class="text-brand-100 text-lg">Here's what's happening with your groups today</p>
                </div>
                <div class="hidden md:block slide-in-right">
                    <a href="{{ route('app.groups.create') }}" wire:navigate
                       class="btn-primary inline-flex items-center px-6 py-3 bg-white text-brand-700 font-semibold rounded-2xl hover:bg-brand-50 transition-smooth shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Group
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid with Staggered Animations -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" data-stagger>
            <!-- Active Groups -->
            <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Active Groups</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2" data-countup="{{ auth()->user()->ownedGroups()->where('status', 'active')->count() }}" data-duration="1500">
                            0
                        </p>
                        <p class="text-xs text-zinc-400">
                            <span class="text-success-600">↑ 2</span> from last month
                        </p>
                    </div>
                    <div class="p-3 bg-brand-50 dark:bg-brand-900/20 rounded-xl animate-pulse-slow">
                        <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Members -->
            <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Members</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2" data-countup="{{ auth()->user()->ownedGroups()->withCount('members')->get()->sum('members_count') }}" data-duration="1800">
                            0
                        </p>
                        <p class="text-xs text-zinc-400">Across all groups</p>
                    </div>
                    <div class="p-3 bg-success-50 dark:bg-success-900/20 rounded-xl animate-pulse-slow" style="animation-delay: 0.5s;">
                        <svg class="h-6 w-6 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Pending</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2" data-countup="{{ \App\Models\PaymentRequest::whereHas('cycle.group', function($q) {
                                $q->where('owner_id', auth()->id());
                            })->whereIn('status', ['pending', 'sent'])->count() }}" data-duration="2000">
                            0
                        </p>
                        <p class="text-xs text-zinc-400">Payment requests</p>
                    </div>
                    <div class="p-3 bg-warning-50 dark:bg-warning-900/20 rounded-xl animate-pulse-slow" style="animation-delay: 1s;">
                        <svg class="h-6 w-6 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- This Month Collection -->
            <div class="card-gradient shadow-glow p-6 text-white scale-in" data-stagger-item>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-brand-100 mb-1">This Month</p>
                        <p class="text-3xl font-bold mb-2" data-countup="{{ (int)(\App\Models\PaymentRequest::whereHas('cycle.group', function($q) {
                                $q->where('owner_id', auth()->id());
                            })->where('status', 'paid')
                            ->whereMonth('paid_at', now()->month)
                            ->sum('amount_cents') / 100) }}" data-prefix="$" data-duration="2200">
                            $0
                        </p>
                        <p class="text-xs text-brand-100">Collected successfully</p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm animate-pulse-slow" style="animation-delay: 1.5s;">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Groups & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Groups -->
            <div class="lg:col-span-2 glass-strong slide-in-bottom">
                <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-700/50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Groups</h3>
                    <a href="{{ route('app.groups.index') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 transition-smooth">
                        View all →
                    </a>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse(auth()->user()->ownedGroups()->latest()->take(5)->get() as $group)
                        <a href="{{ route('app.groups.show', $group) }}" wire:navigate
                           class="block px-6 py-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30 transition-smooth" data-card data-ripple>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1 min-w-0">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-bold text-lg shadow-lg animate-float">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-zinc-900 dark:text-white truncate">{{ $group->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center space-x-2">
                                            <span>{{ $group->members_count ?? $group->members()->count() }} members</span>
                                            <span>•</span>
                                            <span class="font-medium text-gradient">${{ number_format($group->total_amount, 2) }}</span>
                                            <span>•</span>
                                            <span class="text-xs">{{ ucfirst(strtolower(str_replace('_', ' ', $group->cadence))) }}</span>
                                        </p>
                                    </div>
                                </div>
                                <span class="ml-4 flex-shrink-0 px-3 py-1 text-xs font-semibold rounded-full transition-smooth {{ $group->status === 'active' ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400 shadow-glow' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                    {{ ucfirst($group->status) }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center fade-in">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700/50 mb-4 animate-pulse-slow">
                                <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">No groups yet</h4>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">Get started by creating your first bill-splitting group</p>
                            <a href="{{ route('app.groups.create') }}" wire:navigate
                               class="btn-primary inline-flex items-center px-5 py-2.5 bg-brand-600 text-white font-semibold rounded-xl hover:bg-brand-700 transition-smooth shadow-sm hover:scale-105" data-ripple>
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create your first group
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-strong slide-in-right">
                <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-700/50">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3" data-stagger>
                    <a href="{{ route('app.groups.create') }}" wire:navigate
                       class="flex items-center space-x-3 p-4 rounded-xl hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30 transition-smooth group scale-in" data-stagger-item data-ripple>
                        <div class="flex-shrink-0 p-2.5 bg-brand-100 dark:bg-brand-900/30 rounded-lg group-hover:bg-brand-200 dark:group-hover:bg-brand-800/50 transition-smooth">
                            <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">Create Group</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Start a new bill split</p>
                        </div>
                    </a>

                    <a href="{{ route('app.members.index') }}" wire:navigate
                       class="flex items-center space-x-3 p-4 rounded-xl hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30 transition-smooth group scale-in" data-stagger-item data-ripple>
                        <div class="flex-shrink-0 p-2.5 bg-success-100 dark:bg-success-900/30 rounded-lg group-hover:bg-success-200 dark:group-hover:bg-success-800/50 transition-smooth">
                            <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">Manage Members</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">View all participants</p>
                        </div>
                    </a>

                    <a href="{{ route('app.payment-requests.index') }}" wire:navigate
                       class="flex items-center space-x-3 p-4 rounded-xl hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30 transition-smooth group scale-in" data-stagger-item data-ripple>
                        <div class="flex-shrink-0 p-2.5 bg-warning-100 dark:bg-warning-900/30 rounded-lg group-hover:bg-warning-200 dark:group-hover:bg-warning-800/50 transition-smooth">
                            <svg class="h-5 w-5 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">View Payments</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Track payment status</p>
                        </div>
                    </a>

                    <a href="{{ route('app.reports.index') }}" wire:navigate
                       class="flex items-center space-x-3 p-4 rounded-xl hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30 transition-smooth group scale-in" data-stagger-item data-ripple>
                        <div class="flex-shrink-0 p-2.5 bg-zinc-100 dark:bg-zinc-700 rounded-lg group-hover:bg-zinc-200 dark:group-hover:bg-zinc-600 transition-smooth">
                            <svg class="h-5 w-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">Reports</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Export and analyze data</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
