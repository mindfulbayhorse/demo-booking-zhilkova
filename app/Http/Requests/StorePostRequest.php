<?php

namespace App\Http\Requests;

use App\Rules\BookingStep;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    const ERROR_MESS_TIME = 'Некорректное время бронирования';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'starts_at' => 'Дата и время начала',
            'ends_at' => 'Дата и время окончания',
            'room_id' => 'Id переговорной',
            'title' => 'Название бронирования',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', new BookingStep],
            'ends_at' => ['required', 'date', 'after:starts_at', new BookingStep],
            'room_id' => 'required|integer',
            'title' => 'required|string',
        ];
    }

    public function messages(): array
    {

        return [
            'starts_at.required' => 'Время начала обязательно для заполнения',
            'ends_at.required' => 'Время окончания обязательно для заполнения',
            'room_id.required' => 'Id переговорной обязательно',
            'title.required' => 'Название бронирования обязательно для заполнения',
        ];

    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Некорректные данные запроса',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
