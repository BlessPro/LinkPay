<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', Rule::in([Invoice::MODE_FULL, Invoice::MODE_PARTIAL])],
            'deposit_amount' => ['nullable', 'numeric', 'min:0.01', 'required_if:payment_mode,'.Invoice::MODE_PARTIAL],
            'customer_name' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $total = $this->input('total_amount');
            $deposit = $this->input('deposit_amount');

            if ($this->input('payment_mode') === Invoice::MODE_PARTIAL && $total !== null && $deposit !== null) {
                if ((float) $deposit >= (float) $total) {
                    $validator->errors()->add('deposit_amount', 'Deposit must be less than the total amount.');
                }
            }
        });
    }
}
