<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        $table = (new Product())->getTable();
        $requiredString = "$table,sku";
        if($id){
            $requiredString .= ",$id";
        }
        return [
            'product_name' => 'required|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'sku' => "required|string|max:100|unique:$requiredString",
            'category_id' => 'required|numeric',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable',
            'description' => 'nullable|string|max:1000',
            'old_images' => 'nullable|string'
        ];
    }
}
