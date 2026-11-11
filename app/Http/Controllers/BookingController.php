<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        if ($this->bookingService->hasConflict($validated['room_id'], $validated['starts_at'],
        $validated['ends_at'])) {
            return response()->json(['error' => 'Комната занята'], 409);
        }

        $start = Carbon::parse($validated['starts_at']);
        $end = Carbon::parse($validated['ends_at']);
        if ($start->hour < 9 || $start->hour >= 20) {
            return response()->json(['error' => 'Нерабочее время'], 422);
        }

        if (!$this->bookingService->hasDuration($start, $end)){
            return response()->json(
                ['error' => $this->bookingService::ERROR_BOOKING_DURATION], 422);
        }

        $validated['user_id'] = auth()->id();
        $booking = Booking::create($validated);

        Log::withContext(['booking_id' => $booking->id]);
        Log::channel('bookings')->info('Booking created');

        return $booking;
    }

    public function destroy(int $id)
    {
        $booking = DB::table('bookings')->where([
            'user_id' => auth()->user()->id,
            'id' => $id,
        ])->get();

        if ($booking->isEmpty()) {
            return response()->json(['error' => 'Booking not found'], 404);
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
