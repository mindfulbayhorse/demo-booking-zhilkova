<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 *  Class BookingService includes methods for booking management
 * @author Olga Zhilkova
 */
class BookingService
{
    public const ERROR_BOOKING_CONFLICT = 'Переговорная в этот промежуток времени занята';

    /**
     * @param array $data
     * @return bool
     */
    public function hasConflict(array $data): bool
    {
        return Booking::query()
            ->where('room_id', $data['room_id'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->exists();
    }

    /**
     * @param array $data
     * @return Booking
     */
    public function addBooking(array $data): Booking
    {
        $booking = new Booking();
        $booking->room_id = $data['room_id'];
        $booking->starts_at = $data['starts_at'];
        $booking->ends_at = $data['ends_at'];
        $booking->title = $data['title'];
        $booking->user_id = auth()->id();
        $booking->save();

        return $booking;
    }

    /**
     * @param $id
     * @return bool|Booking
     * @throws ModelNotFoundException
     */
    public function findBooking($id): Booking
    {
        return Booking::findOrFail($id);
    }

    /**
     * @param Booking $booking
     * @return bool
     */
    public function checkAuthor(Booking $booking): bool
    {
        if ($booking->user_id !== auth()->id()) {
            return false;
        }

        return true;
    }

    /**
     * @param Booking $booking
     * @return bool
     */
    public function deleteBooking(Booking $booking): bool
    {
        return $booking->delete();
    }

}
