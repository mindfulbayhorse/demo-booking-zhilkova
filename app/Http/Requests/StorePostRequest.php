<?php

namespace App\Http\Requests;

use App\Rules\BookingISOTimeStandart;
use App\Validation\BookingDurationValidation;
use App\Validation\BookingStepValidation;
use App\Validation\BookingWorkingTimeValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
{
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
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'room_id' => 'required|integer',
            'title' => 'required|string',
        ];
    }

    public function messages(): array
    {

        return [
            'starts_at.required' => 'Время начала обязательно для заполнения',
            'ends_at.required' => 'Время окончания обязательно для заполнения',
            'starts_at.date' => 'Время начала должно быть датой',
            'ends_at.date' => 'Время окончания должно быть датой',
            'ends_at.after' => 'Время окончания должно быть после времени начала',
            'room_id.required' => 'Id переговорной обязательно',
            'room_id.integer' => 'Id переговорной должно быть целым числом',
            'title.string' => 'Название бронирования должно быть строкой',
            'title.required' => 'Название бронирования обязательно для заполнения',
        ];

    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {

        return [
            new BookingWorkingTimeValidation,
            new BookingStepValidation,
            new BookingDurationValidation,
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
