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
            'email' => ['nullable', 'email' => 'max:50'],
            'dob' => 'nullable',
            'gender' => 'nullable',
            'address' => 'required|string|max:100',
            'district' => 'required|string|max:50',
            'province' => 'required|string|max:50',
        ];
    }
}
