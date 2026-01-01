<?php

namespace App\Livewire\Members;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Members')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showRemoveModal = false;

    public ?int $editingMemberId = null;

    public ?int $formGroupId = null;

    public string $formEmail = '';

    public string $formPhone = '';

    public string $formShareType = 'AMOUNT';

    public string $formShareValue = '';

    public string $formStatus = 'active';

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    #[Computed]
    public function members(): LengthAwarePaginator
    {
        $query = GroupMember::query()
            ->with(['group', 'user'])
            ->whereHas('group', function ($q) {
                $q->where('owner_id', auth()->id());
            });

        if ($this->search !== '') {
            $query->where(function ($subQuery) {
                $subQuery
                    ->whereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('group', function ($groupQuery) {
                        $groupQuery->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhere('preferred_channel', 'like', '%'.$this->search.'%');
            });
        }

        return $query->latest()->paginate(12);
    }

    #[Computed]
    public function groups(): array
    {
        return auth()->user()
            ->ownedGroups()
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getHasGroupsProperty(): bool
    {
        return count($this->groups()) > 0;
    }

    protected function rules(): array
    {
        $base = [
            'formShareType' => ['required', Rule::in(['AMOUNT', 'PERCENT'])],
        ];

        $shareValueRules = ['required', 'numeric', 'min:0'];

        if ($this->formShareType === 'PERCENT') {
            $shareValueRules[] = 'max:100';
        }

        $base['formShareValue'] = $shareValueRules;

        if ($this->editingMemberId) {
            return array_merge($base, [
                'formStatus' => ['required', Rule::in(['active', 'paused', 'left'])],
            ]);
        }

        return array_merge($base, [
            'formGroupId' => ['required', Rule::in(array_keys($this->groups()))],
            'formEmail' => ['nullable', 'email'],
            'formPhone' => ['nullable', 'string'],
        ]);
    }

    protected function validationAttributes(): array
    {
        return [
            'formGroupId' => 'group',
            'formEmail' => 'email',
            'formPhone' => 'phone',
            'formShareType' => 'share type',
            'formShareValue' => 'share value',
            'formStatus' => 'status',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        if (! $this->hasGroups) {
            session()->flash('error', 'Create a group first before adding members.');

            return;
        }

        $this->resetForm();
        $groups = $this->groups();
        if (! $this->formGroupId && count($groups)) {
            $this->formGroupId = (int) array_key_first($groups);
        }
        $this->resetValidation();
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showRemoveModal = false;
        $this->editingMemberId = null;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function createMember(): void
    {
        $this->validate();

        if (! $this->formEmail && ! $this->formPhone) {
            $this->addError('formEmail', 'Provide an email or phone to find a user.');
            $this->addError('formPhone', 'Provide an email or phone to find a user.');

            return;
        }

        /** @var Group $group */
        $group = Group::where('id', $this->formGroupId)
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $userQuery = User::query();

        if ($this->formEmail && $this->formPhone) {
            // Both provided - find by email OR phone
            $userQuery->where(function ($q) {
                $q->where('email', $this->formEmail)
                    ->orWhere('phone', $this->formPhone);
            });
        } elseif ($this->formEmail) {
            $userQuery->where('email', $this->formEmail);
        } elseif ($this->formPhone) {
            $userQuery->where('phone', $this->formPhone);
        }

        $user = $userQuery->first();

        // Track if this is a new invitation
        $isNewInvitation = false;

        // If user doesn't exist, create a placeholder user and send invitation
        if (! $user) {
            $user = User::create([
                'name' => $this->formEmail ? explode('@', $this->formEmail)[0] : 'User',
                'email' => $this->formEmail ?: null,
                'phone' => $this->formPhone ?: null,
                'password' => bcrypt(Str::random(32)), // Random password
                'role' => 'member',
                'is_placeholder' => true,
            ]);

            $isNewInvitation = true;
        }

        if ($group->members()->where('user_id', $user->id)->exists()) {
            $this->addError('formEmail', 'This user is already a member of the selected group.');

            return;
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'share_type' => $this->formShareType,
            'share_value' => (float) $this->formShareValue,
            'status' => 'active',
        ]);

        // Send invitation if this is a new user
        if ($isNewInvitation && $this->formEmail) {
            $invitation = \App\Models\GroupInvitation::create([
                'group_id' => $group->id,
                'invited_by_user_id' => auth()->id(),
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'token' => \App\Models\GroupInvitation::generateToken(),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'member_data' => [
                    'share_type' => $this->formShareType,
                    'share_value' => (float) $this->formShareValue,
                ],
            ]);

            // Send invitation notification (queued, won't block if mail fails)
            try {
                $user->notify(new \App\Notifications\GroupInvitationNotification($invitation));
                Log::info('Invitation email queued for new member', [
                    'user_id' => $user->id,
                    'email' => $this->formEmail,
                    'group_id' => $group->id,
                ]);
            } catch (\Exception $e) {
                // Log the error but don't block member creation
                Log::warning('Failed to queue member invitation notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $successMessage = $isNewInvitation
            ? 'Member added successfully. An invitation has been sent to their email.'
            : 'Member added successfully.';

        session()->flash('success', $successMessage);

        $this->closeModals();
        $this->resetForm();
        $this->resetPage();
    }

    public function editMember(int $memberId): void
    {
        $member = GroupMember::where('id', $memberId)
            ->whereHas('group', fn ($q) => $q->where('owner_id', auth()->id()))
            ->with(['group', 'user'])
            ->firstOrFail();

        $this->editingMemberId = $member->id;
        $this->formGroupId = $member->group_id;
        $this->formShareType = $member->share_type ?? 'AMOUNT';
        $this->formShareValue = (string) $member->share_value;
        $this->formStatus = $member->status ?? 'active';
        $this->formEmail = $member->user?->email ?? '';
        $this->formPhone = $member->user?->phone ?? '';

        $this->showEditModal = true;
    }

    public function updateMember(): void
    {
        if (! $this->editingMemberId) {
            return;
        }

        $this->validate();

        $member = GroupMember::where('id', $this->editingMemberId)
            ->whereHas('group', fn ($q) => $q->where('owner_id', auth()->id()))
            ->firstOrFail();

        $member->update([
            'share_type' => $this->formShareType,
            'share_value' => (float) $this->formShareValue,
            'status' => $this->formStatus,
        ]);

        session()->flash('success', 'Member updated successfully.');

        $this->closeModals();
        $this->resetForm();
        $this->resetPage();
    }

    public function confirmRemoval(int $memberId): void
    {
        $member = GroupMember::where('id', $memberId)
            ->whereHas('group', fn ($q) => $q->where('owner_id', auth()->id()))
            ->firstOrFail();

        $this->editingMemberId = $member->id;
        $this->showRemoveModal = true;
    }

    public function removeMember(): void
    {
        if (! $this->editingMemberId) {
            return;
        }

        $member = GroupMember::where('id', $this->editingMemberId)
            ->whereHas('group', fn ($q) => $q->where('owner_id', auth()->id()))
            ->firstOrFail();

        $member->update(['status' => 'left']);

        session()->flash('success', 'Member marked as left.');

        $this->closeModals();
        $this->resetForm();
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'formGroupId',
            'formEmail',
            'formPhone',
            'formShareType',
            'formShareValue',
            'formStatus',
        ]);

        $this->formShareType = 'AMOUNT';
        $this->formStatus = 'active';
        $this->formShareValue = '';
    }

    public function updatedFormShareType(): void
    {
        if ($this->formShareType === 'PERCENT' && $this->formShareValue !== '') {
            $this->formShareValue = (string) min(100, max(0, (float) $this->formShareValue));
        }
    }

    public function render()
    {
        return view('livewire.members.index', [
            'members' => $this->members,
            'title' => 'Members',
        ]);
    }
}
