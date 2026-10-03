<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware restricts writes to admins.
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku')) {
            $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'sku' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9\-]+$/', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'sell_price' => ['required', 'numeric', 'min:0', 'max:99999999', 'gte:cost_price'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex' => 'SKU may only contain letters, numbers and dashes.',
            'sell_price.gte' => 'Sell price should not be lower than the cost price.',
        ];
    }
}
