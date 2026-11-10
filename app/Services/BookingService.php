<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class BookingService
{
    const ERROR_BOOKING_DURATION = 'Время бронирования не должно превышать 4 часов';

    const ERROR_BOOKING_INTERVAL = 'Бронирование должно иметь шаг в 15 минут';

    public function hasConflict(int $roomId, string $startsAt, string $endsAt): bool
    {
        return Booking::query()
            ->where('room_id', $roomId)
            ->where('starts_at', '<=', $endsAt)
            ->where('ends_at', '>=', $startsAt)
            ->exists();
    }

    /**
     * Проверяет, что время бронирования не превышает 4 часа
     * @param Carbon $startsAt
     * @param Carbon $endsAt
     * @return bool
     */
    public function hasDuration(Carbon $startsAt, Carbon $endsAt): bool
    {
        return ($startsAt->diffinMinutes($endsAt) <= 4*60 ? true : false);
    }

    public function hasCorrectInterval(Carbon $start, Carbon $end): bool
    {
        return $this->hasTimeDivisibleBy15Minutes($start) && $this->hasTimeDivisibleBy15Minutes($end);
    }

    public function hasTimeDivisibleBy15Minutes(Carbon $time): bool
    {
        return ($time->minute % 15 === 0) && ($time->second === 0);
    }
}
