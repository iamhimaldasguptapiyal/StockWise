<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Staff may only record movements for the branch they are assigned to.
        return $user->isAdmin() || (int) $this->input('branch_id') === (int) $user->branch_id;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            // "in"/"out" take a positive amount; "adjustment" takes a signed correction.
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (in_array($this->input('type'), ['in', 'out'], true) && (int) $this->input('quantity') < 0) {
                $validator->errors()->add('quantity', 'Quantity must be positive for stock in/out.');
            }
        });
    }

    protected function failedAuthorization(): void
    {
        abort(response()->json(['message' => 'You can only record stock movements for your own branch.'], 403));
    }
}
