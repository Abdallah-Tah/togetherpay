@php
    use Illuminate\Support\Str;

    $pageTitle = $title ?? 'Members';
@endphp

<div class="space-y-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage everyone participating in your groups and review their payment details.
                </p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <div class="relative w-full sm:w-72">
                    <label for="member-search" class="sr-only">Search members</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M18 11a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        id="member-search"
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by name, email, or group..."
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-10 pr-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900 disabled:opacity-60"
                    @if(empty($groups)) disabled @endif
                >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Member
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Member</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Group</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Share</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Preferred Channel</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Added</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($members as $member)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                                                {{ $member->user?->initials() ?? Str::of($member->group->name)->substr(0, 2)->upper() }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium">
                                                {{ $member->user?->name ?? 'Pending Invite' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $member->user?->email ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $member->group->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst(strtolower($member->group->cadence)) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    @if($member->share_type === 'PERCENT')
                                        {{ $member->share_value }}%
                                    @else
                                        ${{ number_format($member->share_value, 2) }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ Str::headline(strtolower($member->preferred_channel ?? 'unset')) }}</span>
                                        @if($handle = $member->getPaymentHandle())
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $handle }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                                        {{ $member->status === 'active'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ optional($member->created_at)->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            wire:click="editMember({{ $member->id }})"
                                            class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="confirmRemoval({{ $member->id }})"
                                            class="inline-flex items-center rounded-md border border-red-300 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/40"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405M19 13a7 7 0 10-14 0 7 7 0 0014 0z"></path>
                                    </svg>
                                    <p class="font-medium text-gray-900 dark:text-white">No members match your filters</p>
                                    <p class="mt-1 text-sm">Try adjusting your search or add a member from the group details page.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                {{ $members->links() }}
            </div>
        </div>

        @if($showCreateModal)
            <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4">
                <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add Member</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Invite an existing user into one of your groups.</p>
                        </div>
                        <button type="button" wire:click="closeModals" class="rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form class="mt-6 space-y-5" wire:submit.prevent="createMember">
                        <div>
                            <label for="create-group" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Group</label>
                            <select id="create-group" wire:model="formGroupId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <option value="">Select group...</option>
                                @foreach($groups as $groupId => $groupName)
                                    <option value="{{ $groupId }}">{{ $groupName }}</option>
                                @endforeach
                            </select>
                            @error('formGroupId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="create-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">User Email</label>
                                <input id="create-email" type="email" wire:model.defer="formEmail" placeholder="member@example.com" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="create-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">User Phone</label>
                                <input id="create-phone" type="text" wire:model.defer="formPhone" placeholder="+1 555 000 1234" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="create-share-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Share Type</label>
                                <select id="create-share-type" wire:model="formShareType" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <option value="AMOUNT">Fixed amount</option>
                                    <option value="PERCENT">Percentage</option>
                                </select>
                                @error('formShareType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="create-share-value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Share Value</label>
                                <input id="create-share-value" type="number" step="0.01" wire:model.defer="formShareValue" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formShareValue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="create-preferred-channel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preferred Channel</label>
                                <select id="create-preferred-channel" wire:model="formPreferredChannel" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <option value="VENMO">Venmo</option>
                                    <option value="CASH_APP">Cash App</option>
                                    <option value="CARD">Card</option>
                                </select>
                                @error('formPreferredChannel') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="create-venmo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Venmo Username</label>
                                <input id="create-venmo" type="text" wire:model.defer="formVenmoUsername" placeholder="@username" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formVenmoUsername') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="create-cash-tag" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cash Tag</label>
                                <input id="create-cash-tag" type="text" wire:model.defer="formCashTag" placeholder="$cashtag" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formCashTag') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="create-cash-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cash Phone</label>
                                <input id="create-cash-phone" type="text" wire:model.defer="formCashPhone" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formCashPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="create-cash-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cash Email</label>
                                <input id="create-cash-email" type="email" wire:model.defer="formCashEmail" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formCashEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="button" wire:click="closeModals" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                                Save Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if($showEditModal)
            <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4">
                <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Member</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Adjust collection details for this member.</p>
                        </div>
                        <button type="button" wire:click="closeModals" class="rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form class="mt-6 space-y-5" wire:submit.prevent="updateMember">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Share Type</label>
                                <select wire:model="formShareType" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <option value="AMOUNT">Fixed amount</option>
                                    <option value="PERCENT">Percentage</option>
                                </select>
                                @error('formShareType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Share Value</label>
                                <input type="number" step="0.01" wire:model.defer="formShareValue" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formShareValue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preferred Channel</label>
                                <select wire:model="formPreferredChannel" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <option value="VENMO">Venmo</option>
                                    <option value="CASH_APP">Cash App</option>
                                    <option value="CARD">Card</option>
                                </select>
                                @error('formPreferredChannel') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select wire:model="formStatus" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="left">Left</option>
                                </select>
                                @error('formStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Venmo Username</label>
                                <input type="text" wire:model.defer="formVenmoUsername" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formVenmoUsername') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cash Tag</label>
                                <input type="text" wire:model.defer="formCashTag" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formCashTag') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cash Phone</label>
                                <input type="text" wire:model.defer="formCashPhone" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formCashPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cash Email</label>
                                <input type="email" wire:model.defer="formCashEmail" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                @error('formCashEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="button" wire:click="closeModals" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                                Update Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if($showRemoveModal)
            <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4">
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-10 w-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01m6.938-7a9 9 0 11-13.856 0 9 9 0 0113.856 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Remove member?</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                This will mark the member as <strong>left</strong>. They will no longer be included in future billing cycles.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeModals" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
                        <button type="button" wire:click="removeMember" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                            Confirm Removal
                        </button>
                    </div>
                </div>
            </div>
        @endif
</div>
