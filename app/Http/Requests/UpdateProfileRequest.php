<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'phone_country' => ['nullable', 'string', Rule::in(['+233'])],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'settlement_bank_code' => ['nullable', 'string', 'max:20', 'required_with:account_number,account_name'],
            'account_number' => ['nullable', 'string', 'max:20', 'required_with:settlement_bank_code,account_name'],
            'account_name' => ['nullable', 'string', 'max:160', 'required_with:settlement_bank_code,account_number'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $phoneNumber = $this->input('phone_number');
            $country = $this->input('phone_country', '+233');

            if (! $phoneNumber) {
                return;
            }

            $digits = preg_replace('/\D+/', '', $phoneNumber);
            if (! $digits) {
                $validator->errors()->add('phone_number', 'Phone number must contain digits.');
                return;
            }

            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }

            if ($country === '+233' && strlen($digits) !== 9) {
                $validator->errors()->add('phone_number', 'Phone number must be 9 digits after removing the leading 0.');
            }
        });
    }
}
