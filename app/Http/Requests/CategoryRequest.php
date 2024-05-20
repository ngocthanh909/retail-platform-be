<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
        $table = (new Category())->getTable();
        $requiredString = "$table,category_code";
        if($id){
            $requiredString .= ",$id";
        }
        return [
            'category_name' => 'required|string|max:255',
            'category_image' => 'nullable|image|max:2048',
            'category_code' => "required|string|max:100|unique:$requiredString",
            'status' => 'nullable',
            'commission_rate' => ['nullable', 'double' => 'max:100']
        ];
    }
}
