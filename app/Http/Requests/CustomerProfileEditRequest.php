<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileEditRequest extends FormRequest
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
            'avatar' => ['image' => 'max:2048', 'nullable'],
            'customer_name' => 'required|string|max:200',
            'dob' => ['nullable', 'date'],
            'gender' => 'nullable',
            'address' => 'required|string|max:100',
            'district_id' => 'required|numeric',
            'province_id' => 'required|numeric',
        ];
    }
}
