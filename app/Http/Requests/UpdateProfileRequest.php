<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'settlement_bank_code' => ['nullable', 'string', 'max:20', 'required_with:account_number,account_name'],
            'account_number' => ['nullable', 'string', 'max:20', 'required_with:settlement_bank_code,account_name'],
            'account_name' => ['nullable', 'string', 'max:160', 'required_with:settlement_bank_code,account_number'],
        ];
    }
}
