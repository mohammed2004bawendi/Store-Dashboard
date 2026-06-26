<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:قيد التنفيذ,تم التوصيل,ملغي',
            'total_price' => 'nullable|numeric|min:0',

            'customer' => 'nullable|array',
            'customer.name' => 'nullable|string|max:255',
            'customer.address' => 'nullable|string|max:255',

            'products' => 'nullable|array',
            'products.*.id' => 'nullable|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
        ];
    }
}
