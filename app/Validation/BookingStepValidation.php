<?php

namespace App\Validation;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class BookingStepValidation
{
    public const ERROR_WRONG_START_TIME_STEP = 'Время начала бронирования должно быть должно быть кратно шагу в 15 минут';

    public const ERROR_WRONG_END_TIME_STEP = 'Время окончания бронирования должно быть должно быть кратно шагу в 15 минут';

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->getData();
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        if (($startsAt->minute % 15 !== 0 || $startsAt->second !== 0) && $startsAt !== 0) {
            $validator->errors()->add('starts_at', self::ERROR_WRONG_START_TIME_STEP);
        }

        if (($endsAt->minute % 15 !== 0 || $endsAt->second !== 0) && $endsAt !== 0) {
            $validator->errors()->add('ends_at', self::ERROR_WRONG_END_TIME_STEP);
        }

    }
}
