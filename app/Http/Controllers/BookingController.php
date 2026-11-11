<?php

namespace App\Http\Controllers;

use App\Exeptions\BookingExeption;
use App\Http\Requests\StorePostRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use \Throwable;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function index(Request $request)
    {
        return Booking::where('user_id', $request->user()->id)
            ->orderByRaw('starts_at DESC')
            ->paginate(20);
    }

    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $lock = Cache::lock("booking-{$validated['room_id']}-{$validated['starts_at']}-{$validated['ends_at']}", 2);

        if (!$lock->get()) {
            return response()->json(['message' => 'Бронирование с теми же данными уже в процессе, попробуйте позже'], 409);
        }

        try {
            if ($this->bookingService->hasConflict($validated)) {
                throw new BookingExeption();
            }
            $booking = $this->bookingService->addBooking($validated);
            Log::withContext(['booking_id' => $booking->id]);
            Log::channel('bookings')->info('Booking created');

        } catch (BookingExeption $e) {
            return response()->json(['error' => $this->bookingService::ERROR_BOOKING_CONFLICT], 409);
        } catch (Throwable) {
            return response()->json(['error' => $this->bookingService::ERROR_BOOKING_ERROR], 500);
        } finally {
            $lock->release();
        }

        return $booking;
    }

    public function destroy(int $id)
    {
        try {
            $booking = $this->bookingService->findBooking($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Данное бронирование не найдено'], 404);
        }

        if ($this->bookingService->checkAuthor($booking) === false) {
            return response()->json(['error' => 'Вы не являетесь автором бронирования'], 403);
        }

        try {
            $this->bookingService->deleteBooking($booking);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка при удалении бронирования'], 500);
        }

        return response()->noContent();
    }

    public function today(Room $room)
    {
        return $room->bookings()
            ->whereDate('starts_at', today())
            ->get()
            ->map(fn ($b) => [
                'time' => $b->starts_at.' – '.$b->ends_at,
                'author' => $b->user->name,
            ]);
    }
}
