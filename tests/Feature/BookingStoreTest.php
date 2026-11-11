<?php

namespace Tests\Feature;

use App\Models\Booking;
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


    public function test_bookings_start_time_conflicts()
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse('next monday')->setTime(10, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(14, 15)->toIso8601String(),
        ]);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(12, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(15, 15)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(409)
            ->assertJson([
                'error' => BookingService::ERROR_BOOKING_CONFLICT,
            ]);
    }

    public function test_bookings_have_no_conflicts()
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse('next monday')->setTime(10, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(14, 15)->toIso8601String(),
        ]);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(14, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(15, 15)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(201);
    }

    public function test_bookings_end_time_conflicts()
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse('next monday')->setTime(10, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(14, 15)->toIso8601String(),
        ]);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(9, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(10, 30)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(409)
            ->assertJson([
                'error' => BookingService::ERROR_BOOKING_CONFLICT,
            ]);
    }


    public function test_booking_is_created(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(14, 15)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(15, 15)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])->assertStatus(201);
    }


    public function test_booking_is_deleted_by_author(): void
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse('next monday')->setTime(10, 15),
            'ends_at' => Carbon::parse('next monday')->setTime(14, 15),
        ]);

        $this->deleteJson('/api/bookings/' . $booking->id)
            ->assertNoContent();

    }

    public function test_booking_is_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $this->deleteJson('/api/bookings/' . 500)
            ->assertNotFound();

    }


    public function test_booking_cannot_be_deleted_by_another_user(): void
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse('next monday')->setTime(10, 15),
            'ends_at' => Carbon::parse('next monday')->setTime(14, 15),
        ]);

        $user2 = User::factory()->create();
        Sanctum::actingAs($user2);

        $this->deleteJson('/api/bookings/' . $booking->id)
            ->assertStatus(403);

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

}
