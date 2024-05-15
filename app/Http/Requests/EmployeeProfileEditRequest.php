<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeProfileEditRequest extends FormRequest
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
        $id = request()->id;
        $table = (new User())->getTable();
        $requiredString = "$table,phone";
        if($id){
            $requiredString .= ",$id";
        }
        return [
            'name' => 'required|string|max:200',
            'password' => request()->id ? "nullable" : "required|string|max:60",
            'email' => ['nullable', 'email' => 'max:50'],
            'dob' => ['nullable', 'date'],
            'gender' => 'string|numeric',
            'address' => ['nullable', 'string' => 'max:100'],
            'status' => 'nullable',
        ];
    }
}
