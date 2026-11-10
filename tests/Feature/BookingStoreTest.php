<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $office = Office::factory()->create();
        $this->room = Room::factory()->for($office)->create();
    }

    public function test_store_rejects_invalid_booking_dates(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => 'not-a-date',
            'ends_at' => 'also-not-a-date',
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    }

    public function test_booking_lasts_4_hours_only(): void
    {
        $start = $startsAt = Carbon::parse('next monday')->setTime(15, 0);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $start->add('4 hours')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(201);
    }

    public function test_booking_has_incorrect_duration(): void
    {
        $start = $startsAt = Carbon::parse('next monday')->setTime(15, 0);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $start->add('4 hours 1 minute')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(422)
            ->assertJson([
                'error' => BookingService::ERROR_BOOKING_DURATION,
            ]);
    }

    public function test_booking_has_correct_intervals(): void
    {
        $start = $startsAt = Carbon::parse('next monday')->setTime(10, 15);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $start->add('4 hours')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(201);
    }

    public function test_booking_has_wrong_time_intervals(): void
    {
        $start = $startsAt = Carbon::parse('next monday')->setTime(10, 37);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $start->add('4 hours')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(422)
            ->assertJson([
                'error' => BookingService::ERROR_BOOKING_INTERVAL,
            ]);
    }
}
