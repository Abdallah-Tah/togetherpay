<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required_without:amount_cents', 'numeric', 'min:0.5'],
            'amount_cents' => ['required_without:amount', 'integer', 'min:50'],
            'note' => ['nullable', 'string', 'max:160'],
            'cycle_id' => ['nullable', 'integer', 'exists:billing_cycles,id'],
            'send_now' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => is_numeric($this->amount) ? (float) $this->amount : $this->amount,
            ]);
        }
    }
}
