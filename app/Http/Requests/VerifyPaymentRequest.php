<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by route middleware (auth + verified + admin)
    }

    public function rules(): array
    {
        return [
            'org_name' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'min:3', 'max:64'],
            'plan_slug' => ['nullable', 'string', 'in:community,barangay,city'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'org_name' => trim((string) $this->input('org_name')),
            'reference' => trim((string) $this->input('reference')),
        ]);
    }
}
