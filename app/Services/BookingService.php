<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class BookingService
{
    const ERROR_BOOKING_DURATION = 'Время бронирования не должно превышать 4 часов';

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
        return ($startsAt->diffinMinutes($endsAt) < 4*60 ? true : false);
    }
}
