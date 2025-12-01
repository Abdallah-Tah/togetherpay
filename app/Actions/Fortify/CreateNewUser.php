<?php

namespace App\Actions\Fortify;

use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Check if registering from invitation
        $invitation = null;
        if (!empty($input['invitation_token'])) {
            $invitation = GroupInvitation::where('token', $input['invitation_token'])
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            // If invitation exists, use its email
            if ($invitation) {
                $input['email'] = $invitation->email;
            }
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        // Send welcome email
        $user->notify(new WelcomeNotification);

        // Auto-accept invitation if exists
        if ($invitation) {
            DB::transaction(function () use ($invitation, $user) {
                GroupMember::create([
                    'group_id' => $invitation->group_id,
                    'user_id' => $user->id,
                    'name' => $invitation->member_data['name'] ?? $user->name,
                    'email' => $user->email,
                    'phone' => $invitation->phone ?? $user->phone,
                    'share_count' => $invitation->member_data['share_count'] ?? 1,
                    'venmo_username' => $invitation->member_data['venmo_username'] ?? $user->venmo_username ?? null,
                    'cashapp_cashtag' => $invitation->member_data['cashapp_cashtag'] ?? $user->cash_tag ?? null,
                ]);

                $invitation->markAsAccepted($user);
            });
        }

        return $user;
    }
}
