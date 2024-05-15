<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
        $table = (new Customer())->getTable();
        $requiredString = "$table,phone";
        if($id){
            $requiredString .= ",$id";
        }
        return [
            'phone' => "required|string|max:20|unique:$requiredString",
            'password' => request()->id ? "nullable" : "required|string|max:60",
            'customer_name' => 'required|string|max:200',
            'responsible_staff' => ['nullable', 'numeric'],
            'address' => 'required|string|max:100',
            'district' => 'required|string|max:50',
            'province' => 'required|string|max:50',
            'status' => 'nullable',
        ];
    }
}
