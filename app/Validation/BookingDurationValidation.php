<?php

namespace App\Validation;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class BookingDurationValidation
{
    public const ERROR_WRONG_DURATION = 'Продолжительность бронирования не должно превышать 4 часов';

    public const ERROR_KEY = 'booking_time';
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->getData();
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        if ($startsAt->diffinMinutes($endsAt) > 4 * 60 ? true : false){
            $validator->errors()->add(self::ERROR_KEY, self::ERROR_WRONG_DURATION);
        }

    }
}
