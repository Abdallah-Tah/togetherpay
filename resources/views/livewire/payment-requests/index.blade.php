@php($pageTitle = $title ?? 'Payments')

<div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track the status of every payment request across your groups.</p>
        </div>
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pending'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['paid'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['failed'] }}</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button wire:click="$set('filter', 'all')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition {{ $filter === 'all' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        All
                    </button>
                    <button wire:click="$set('filter', 'pending')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition {{ $filter === 'pending' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Pending
                    </button>
                    <button wire:click="$set('filter', 'paid')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition {{ $filter === 'paid' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Paid
                    </button>
                    <button wire:click="$set('filter', 'failed')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition {{ $filter === 'failed' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Failed
                    </button>
                </nav>
            </div>

            <!-- Requests List -->
            <div class="p-6">
                @if($requests->count() > 0)
                    <div class="space-y-4">
                        @foreach($requests as $request)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <div class="flex items-center space-x-4 flex-1 min-w-0">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                                                {{ $request->member->user->initials() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ $request->member->user->name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                            {{ $request->cycle->group->name }} · {{ $request->cycle->period_start->format('M d') }} - {{ $request->cycle->period_end->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900 dark:text-white">${{ number_format($request->amount, 2) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @if($request->paid_at)
                                                Paid {{ $request->paid_at->diffForHumans() }}
                                            @elseif($request->sent_at)
                                                Sent {{ $request->sent_at->diffForHumans() }}
                                            @else
                                                Created {{ $request->created_at->diffForHumans() }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full
                                        {{ $request->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                           ($request->status === 'failed' || $request->status === 'expired' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                           'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
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
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No payment requests found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Payment requests will appear here once billing cycles are created
                        </p>
                    </div>
                @endif
            </div>
        </div>
</div>
