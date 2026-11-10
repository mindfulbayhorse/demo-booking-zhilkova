<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'room_id' => 'required|integer',
            'title' => 'required|string'
        ];
    }

    public function messages(): array
    {

        return [
            'starts_at.required' => 'A start date is required',
            'ends_at.required' => 'An end date is required',
            'room_id.required' => 'A room is required',
            'title.required' => 'A title for booking is required',
        ];

    }
}
