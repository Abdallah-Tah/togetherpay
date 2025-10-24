<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'cadence' => ['sometimes', 'in:MONTHLY,EVERY_4_WEEKS'],
            'due_day' => ['sometimes', 'integer', 'min:1', 'max:28'],
            'grace_days' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,paused,archived'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('total_amount')) {
            $this->merge([
                'total_amount_cents' => $this->total_amount * 100,
            ]);
        }
    }
}
