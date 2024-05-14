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
            'email' => 'nullable|email|max:50',
            'customer_name' => 'required|string|max:200',
            'responsible_staff' => 'numeric',
            'address' => 'required|string|max:100',
            'district' => 'required|string|max:50',
            'province' => 'required|string|max:50',
            'avatar' => 'nullable|image|max:2048',
            'dob' => ['nullable', 'date'],
            'gender' => 'nullable|numeric'
        ];
    }
}
