<?php

namespace App\Validation;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class BookingDurationValidation
{
    public const ERROR_WRONG_DURATION = 'Продолжительность бронирования не должно превышать 4 часов';

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->getData();
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        if ($startsAt->diffinMinutes($endsAt) > 4 * 60 ? true : false){
            $validator->errors()->add('ends_at', self::ERROR_WRONG_DURATION);
        }

    }
}
