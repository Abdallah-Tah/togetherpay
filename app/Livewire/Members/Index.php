<?php

namespace App\Livewire\Members;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

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
    public string $formPreferredChannel = 'VENMO';
    public string $formVenmoUsername = '';
    public string $formCashTag = '';
    public string $formCashPhone = '';
    public string $formCashEmail = '';
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
            'formPreferredChannel' => ['required', Rule::in(['VENMO', 'CASH_APP', 'CARD'])],
            'formVenmoUsername' => ['nullable', 'string', Rule::requiredIf($this->formPreferredChannel === 'VENMO')],
            'formCashTag' => ['nullable', 'string'],
            'formCashPhone' => ['nullable', 'string'],
            'formCashEmail' => ['nullable', 'email'],
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
            'formEmail' => ['nullable', 'email', 'exists:users,email'],
            'formPhone' => ['nullable', 'string', 'exists:users,phone'],
        ]);
    }

    protected function validationAttributes(): array
    {
        return [
            'formGroupId' => 'group',
            'formEmail' => 'email',
            'formPhone' => 'phone',
            'formPreferredChannel' => 'preferred channel',
            'formVenmoUsername' => 'Venmo username',
            'formCashTag' => 'Cash App tag',
            'formCashPhone' => 'Cash App phone',
            'formCashEmail' => 'Cash App email',
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

        if (!$this->formEmail && !$this->formPhone) {
            $this->addError('formEmail', 'Provide an email or phone to find a user.');
            $this->addError('formPhone', 'Provide an email or phone to find a user.');
            return;
        }

        /** @var Group $group */
        $group = Group::where('id', $this->formGroupId)
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $userQuery = User::query();

        if ($this->formEmail) {
            $userQuery->where('email', $this->formEmail);
        }

        if ($this->formPhone) {
            $userQuery->orWhere('phone', $this->formPhone);
        }

        $user = $userQuery->first();

        if (!$user) {
            $this->addError('formEmail', 'We could not find a registered user with that email or phone.');
            return;
        }

        if ($group->members()->where('user_id', $user->id)->exists()) {
            $this->addError('formEmail', 'This user is already a member of the selected group.');
            return;
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'preferred_channel' => $this->formPreferredChannel,
            'venmo_username' => $this->formVenmoUsername,
            'cash_tag' => $this->formCashTag,
            'cash_phone' => $this->formCashPhone,
            'cash_email' => $this->formCashEmail,
            'share_type' => $this->formShareType,
            'share_value' => (float) $this->formShareValue,
            'status' => 'active',
        ]);

        session()->flash('success', 'Member added successfully.');

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
        $this->formPreferredChannel = $member->preferred_channel ?? 'VENMO';
        $this->formVenmoUsername = $member->venmo_username ?? '';
        $this->formCashTag = $member->cash_tag ?? '';
        $this->formCashPhone = $member->cash_phone ?? '';
        $this->formCashEmail = $member->cash_email ?? '';
        $this->formShareType = $member->share_type ?? 'AMOUNT';
        $this->formShareValue = (string) $member->share_value;
        $this->formStatus = $member->status ?? 'active';
        $this->formEmail = $member->user?->email ?? '';
        $this->formPhone = $member->user?->phone ?? '';

        $this->showEditModal = true;
    }

    public function updateMember(): void
    {
        if (!$this->editingMemberId) {
            return;
        }

        $this->validate();

        $member = GroupMember::where('id', $this->editingMemberId)
            ->whereHas('group', fn ($q) => $q->where('owner_id', auth()->id()))
            ->firstOrFail();

        $member->update([
            'preferred_channel' => $this->formPreferredChannel,
            'venmo_username' => $this->formVenmoUsername ?: null,
            'cash_tag' => $this->formCashTag ?: null,
            'cash_phone' => $this->formCashPhone ?: null,
            'cash_email' => $this->formCashEmail ?: null,
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
        if (!$this->editingMemberId) {
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
            'formPreferredChannel',
            'formVenmoUsername',
            'formCashTag',
            'formCashPhone',
            'formCashEmail',
            'formShareType',
            'formShareValue',
            'formStatus',
        ]);

        $this->formPreferredChannel = 'VENMO';
        $this->formShareType = 'AMOUNT';
        $this->formStatus = 'active';
        $this->formShareValue = '';
    }

    public function updatedFormPreferredChannel(): void
    {
        if ($this->formPreferredChannel !== 'VENMO') {
            $this->formVenmoUsername = '';
        }
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
