<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Allow email/phone for both existing users AND new invitations
            'email' => ['required_without:phone', 'email'],
            'phone' => ['required_without:email', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'preferred_channel' => ['sometimes', 'in:VENMO,CASH_APP,CARD'],
            'venmo_username' => ['required_if:preferred_channel,VENMO', 'nullable', 'string'],
            'cash_tag' => ['nullable', 'string'],
            'cash_phone' => ['nullable', 'string'],
            'cash_email' => ['nullable', 'email'],
            'share_type' => ['sometimes', 'in:AMOUNT,PERCENT'],
            'share_value' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Either email or phone is required',
            'phone.required_without' => 'Either email or phone is required',
            'venmo_username.required_if' => 'Venmo username is required when preferred channel is Venmo',
        ];
    }
}
