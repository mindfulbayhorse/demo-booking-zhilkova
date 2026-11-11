<?php

namespace App\Validation;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class BookingWorkingTimeValidation
{
    public const ERROR_OUTSIDE_WORKING_HOURS = 'Бронь должна укладываться в рабочее время офиса 9:00–20:00';

    public const ERROR_PERIOD_MORE_ONE_DAY = 'Бронь должна начинаться и заканчиваться в пределах одного рабочего дня офиса';

    public const ERROR_KEY = 'booking_time';

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->getData();
        $room = Room::with('office')->find($data['room_id']);

        $start = Carbon::parse($data['starts_at'])->setTimezone($room->office->timezone);
        $end = Carbon::parse($data['ends_at'])->setTimezone($room->office->timezone);

        if (!$start->isSameDay($end)) {
            $validator->errors()->add(self::ERROR_KEY, self::ERROR_PERIOD_MORE_ONE_DAY);
            return;
        }

        // Задаем границы рабочего дня офиса для даты начала брони
        $workStart = $start->copy()->setTime(9, 0, 0);
        $workEnd = $start->copy()->setTime(20, 0, 0);

        if ($start->lt($workStart) || $end->gt($workEnd)) {
            $validator->errors()->add(self::ERROR_KEY, self::ERROR_OUTSIDE_WORKING_HOURS . " ({$room->office->timezone})");
        }

    }
}
