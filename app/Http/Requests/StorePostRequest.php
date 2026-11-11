<?php

namespace App\Http\Requests;

use App\Models\Room;
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
        $room = Room::with('office')->findOrFail($this->input('room_id'));
        $timezone = $room->office->timezone;
        return [
            'starts_at' => ['required', 'date', 'date_format:Y-m-d\TH:i:sP'],
            'ends_at' => ['required', 'date', 'date_format:Y-m-d\TH:i:sP', 'after:starts_at'],
            'room_id' => 'required|integer|exists:' . Room::class . ',id',
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
            'room_id.exists' => 'Переговорная с таким id не найдена',
            'title.string' => 'Название бронирования должно быть строкой',
            'title.required' => 'Название бронирования обязательно для заполнения',
            'starts_at.date_format' => 'Время начала должно быть в формате ISO 8601',
            'ends_at.date_format' => 'Время окончания должно быть в формате ISO 8601',
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
