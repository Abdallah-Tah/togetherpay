<div>
    @php
        $pageTitle = $title ?? 'Payments';
    @endphp

    <div class="space-y-8">
        <!-- Hero Section -->
    <div class="card-gradient p-8 text-white animate-gradient fade-in">
        <div class="flex items-center justify-between">
            <div class="slide-in-bottom">
                <h1 class="text-3xl font-bold mb-2">{{ $pageTitle }}</h1>
                <p class="text-brand-100 text-lg">Track the status of every payment request across your groups.</p>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" data-stagger>
        <!-- Total Requests -->
        <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Requests</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ $stats['total'] }}</p>
                </div>
                <div class="p-3 bg-brand-50 dark:bg-brand-900/20 rounded-xl animate-pulse-slow">
                    <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Pending</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ $stats['pending'] }}</p>
                </div>
                <div class="p-3 bg-warning-50 dark:bg-warning-900/20 rounded-xl animate-pulse-slow" style="animation-delay: 0.5s;">
                    <svg class="h-6 w-6 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Paid -->
        <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Paid</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ $stats['paid'] }}</p>
                </div>
                <div class="p-3 bg-success-50 dark:bg-success-900/20 rounded-xl animate-pulse-slow" style="animation-delay: 1s;">
                    <svg class="h-6 w-6 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="card-elevated glass bg-white dark:bg-zinc-800 p-6 scale-in" data-stagger-item>
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Failed</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ $stats['failed'] }}</p>
                </div>
                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl animate-pulse-slow" style="animation-delay: 1.5s;">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card-elevated glass bg-white dark:bg-zinc-800 overflow-hidden fade-in" style="animation-delay: 0.2s;">
        <div class="border-b border-zinc-100 dark:border-zinc-700/50">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button wire:click="$set('filter', 'all')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-smooth {{ $filter === 'all' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                    All
                </button>
                <button wire:click="$set('filter', 'pending')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-smooth {{ $filter === 'pending' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                    Pending
                </button>
                <button wire:click="$set('filter', 'paid')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-smooth {{ $filter === 'paid' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                    Paid
                </button>
                <button wire:click="$set('filter', 'failed')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-smooth {{ $filter === 'failed' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                    Failed
                </button>
            </nav>
        </div>

        <!-- Requests List -->
        <div class="p-6">
            @if($requests->count() > 0)
                <div class="space-y-3">
                    @foreach($requests as $request)
                        <div class="flex items-center justify-between p-4 bg-zinc-50/50 dark:bg-zinc-900/30 rounded-xl hover:bg-zinc-100/50 dark:hover:bg-zinc-700/30 transition-smooth border border-zinc-100 dark:border-zinc-700/50 backdrop-blur-sm">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-sm font-semibold shadow-sm">
                                        {{ $request->member->user->initials() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-zinc-900 dark:text-white truncate">
                                        {{ $request->member->user->name }}
                                    </p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">
                                        {{ $request->cycle->group->name }} · {{ $request->cycle->period_start->format('M d') }} - {{ $request->cycle->period_end->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="font-semibold text-zinc-900 dark:text-white">${{ number_format($request->amount, 2) }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        @if($request->paid_at)
                                            Paid {{ $request->paid_at->diffForHumans() }}
                                        @elseif($request->sent_at)
                                            Sent {{ $request->sent_at->diffForHumans() }}
                                        @else
                                            Created {{ $request->created_at->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>
                                <span class="flex-shrink-0 px-3 py-1 text-xs font-semibold rounded-full transition-smooth
                                    {{ $request->status === 'paid' ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400 shadow-glow' :
                                       ($request->status === 'failed' || $request->status === 'expired' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                       'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $requests->links() }}
                </div>
            @else
                <div class="text-center py-12 fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700/50 mb-4 animate-pulse-slow">
                        <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">No payment requests found</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Payment requests will appear here once billing cycles are created
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
</div>
