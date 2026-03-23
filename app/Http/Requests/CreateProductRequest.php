<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusOptions = array_keys(Product::statusOptions());

        if ($this->has('products')) {
            return [
                'products' => ['required', 'array', 'min:1', 'max:20'],
                'products.*.name' => ['required', 'string', 'max:120'],
                'products.*.description' => ['nullable', 'string', 'max:2000'],
                'products.*.price' => ['required', 'numeric', 'min:0.01'],
                'products.*.stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'products.*.low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'products.*.image' => ['nullable', 'image', 'max:6144'],
                'products.*.is_active' => ['nullable', 'boolean'],
                'products.*.status' => ['nullable', 'string', Rule::in($statusOptions)],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'image' => ['nullable', 'image', 'max:6144'],
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
        ];
    }
}
