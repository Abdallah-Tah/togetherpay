@php($pageTitle = $title ?? 'Groups')

<div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stay on top of your splitting groups and quickly create new ones.</p>
        </div>
        <!-- Header with search and create -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1 max-w-lg">
                <label for="search" class="sr-only">Search groups</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live="search"
                           type="search"
                           id="search"
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Search groups...">
                </div>
            </div>

            <a href="{{ route('groups.create') }}" wire:navigate
               class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Group
            </a>
        </div>

        <!-- Groups grid -->
        @if($groups->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($groups as $group)
                    <a href="{{ route('groups.show', $group) }}" wire:navigate
                       class="block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">
                        <!-- Group header -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                        {{ $group->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}
                                    </p>
                                </div>
                                <span class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full
                                    {{ $group->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ ucfirst($group->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Group details -->
                        <div class="p-6 space-y-3">
                            <div class="flex items-center text-sm">
                                <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">${{ number_format($group->total_amount, 2) }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">/{{ strtolower($group->cadence) }}</span>
                                </span>
                            </div>

                            <div class="flex items-center text-sm">
                                <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">
                                    Due day {{ $group->due_day }} · {{ $group->grace_days }} day grace period
                                </span>
                            </div>

                            @if($group->currentCycle())
                                @php $cycle = $group->currentCycle(); @endphp
                                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Current cycle</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ round($cycle->collection_rate) }}% collected
                                        </span>
                                    </div>
                                    <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full transition-all"
                                             style="width: {{ $cycle->collection_rate }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- Empty state -->
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                    @if($search)
                        No groups found
                    @else
                        No groups yet
                    @endif
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if($search)
                        Try adjusting your search criteria
                    @else
                        Get started by creating your first group
                    @endif
                </p>
                @if(!$search)
                    <div class="mt-6">
                        <a href="{{ route('groups.create') }}" wire:navigate
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Group
                        </a>
                    </div>
                @endif
            </div>
        @endif
</div>
