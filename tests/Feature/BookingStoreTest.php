<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingStoreTest extends TestCase
{
    public function test_store_rejects_invalid_booking_dates(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/bookings', [
            'starts_at' => 'not-a-date',
            'ends_at' => 'also-not-a-date',
            'room_id' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    }

    public function test_authorized_user_only_can_create_booking(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/bookings/store')
            ->assertUnauthorized();
    }

    public function test_booking_lasts_4_hours_only(): void
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        $room = Room::factory()->for($office)->create();
        $start = $startsAt = Carbon::parse('next monday')->setTime(15, 0);

        Sanctum::actingAs($user);

        $this->postJson('/api/bookings', [
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $start->add('4 hours 1 minute')->toIso8601String(),
            'room_id' => $room->id,
            'title' => 'Test booking'
        ])
            ->assertStatus(422)
            ->assertJson([
                'error' => BookingService::ERROR_BOOKING_DURATION,
            ]);
    }

    public function test_booking_is_for_diff_offices(): void {}
}
