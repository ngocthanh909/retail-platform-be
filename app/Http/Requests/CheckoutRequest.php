<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'customer_id' => 'required|numeric',
            'customer_name' => request()->customer_id == 0 ? 'required' : '',
            'customer_address' => request()->customer_id == 0 ? 'required' : '',
            'customer_phone' => request()->customer_id == 0 ? 'required' : '',
            'products' => 'required|array',
            'discount_code' => 'nullable',
            'note' => 'nullable'
        ];
    }
}
