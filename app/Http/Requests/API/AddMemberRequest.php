<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowExisting = $this->boolean('allow_existing');
        $emailRules = ['required', 'email'];
        if (! $allowExisting) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            // Require email for identity and invites
            'email' => $emailRules,
            'phone' => ['nullable', 'string'],
            'name' => [$allowExisting ? 'nullable' : 'required', 'string', 'max:255'],
            'preferred_channel' => ['sometimes', 'in:VENMO,CASH_APP,CARD'],
            'venmo_username' => ['required_if:preferred_channel,VENMO', 'nullable', 'string'],
            'cash_tag' => ['nullable', 'string'],
            'cash_phone' => ['nullable', 'string'],
            'cash_email' => ['nullable', 'email'],
            'share_type' => ['sometimes', 'in:AMOUNT,PERCENT'],
            'share_value' => ['required', 'numeric', 'min:0'],
            'allow_existing' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.unique' => 'Email already exists. Use the existing member option.',
            'venmo_username.required_if' => 'Venmo username is required when preferred channel is Venmo',
        ];
    }
}
