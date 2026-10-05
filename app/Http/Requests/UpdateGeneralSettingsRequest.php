<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by EnsureOrganizationAdmin middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'monthly_due_amount' => ['required', 'numeric', 'min:0'],
            'manual_payment_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
