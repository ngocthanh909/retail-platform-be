<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationCampaignRequest extends FormRequest
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
            'title' => 'required|string|max:300',
            'content' => 'required|string|max:1000',
            'image' => ['nullable', 'file' => 'max:2048'],
            'receiver_id' => 'required',
            'repeat' => 'string',
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'date_format:H:i:s'],
            'status' => 'nullable'
        ];
    }
}
