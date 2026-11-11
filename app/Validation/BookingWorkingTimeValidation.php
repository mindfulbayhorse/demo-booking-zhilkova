<?php

namespace App\Validation;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class BookingWorkingTimeValidation
{
    public const ERROR_START_OUTSIDE_WORKING_HOURS = 'Время начала бронирования должно быть в рабочее время с 09:00 до 20:00';

    public const ERROR_END_OUTSIDE_WORKING_HOURS = 'Время начала бронирования должно быть в рабочее время с 09:00 до 20:00';

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->getData();
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        if ($startsAt->hour < 9 || $startsAt->hour > 20) {
            $validator->errors()->add('starts_at', self::ERROR_START_OUTSIDE_WORKING_HOURS);
        }

        if ($endsAt->hour < 9 || $endsAt->hour > 20) {
            $validator->errors()->add('ends_at', self::ERROR_END_OUTSIDE_WORKING_HOURS);
        }

    }
}
