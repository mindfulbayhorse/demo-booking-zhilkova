<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Room;
use App\Models\User;
use App\Validation\BookingWorkingTimeValidation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Validation\BookingDurationValidation;
use \App\Validation\BookingStepValidation;

class BookingTimeTest extends TestCase
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

    public function test_booking_starts_outside_of_working_hours(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(8, 0)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(12, 0)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at'])
            ->assertJsonPath('errors.starts_at', [BookingWorkingTimeValidation::ERROR_START_OUTSIDE_WORKING_HOURS]);
    }

    public function test_booking_ends_outside_of_working_hours(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(9, 0)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(21, 0)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ends_at'])
            ->assertJsonPath('errors.ends_at', [BookingWorkingTimeValidation::ERROR_END_OUTSIDE_WORKING_HOURS]);;;
    }


    public function test_booking_is_outside_of_working_hours(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(7, 0)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(21, 0)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at', 'ends_at'])
            ->assertJsonPath('errors.starts_at', [BookingWorkingTimeValidation::ERROR_START_OUTSIDE_WORKING_HOURS])
            ->assertJsonPath('errors.ends_at', [BookingWorkingTimeValidation::ERROR_END_OUTSIDE_WORKING_HOURS]);;
    }

    public function test_start_time_is_not_on_correct_step(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(10, 37)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(12, 0)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at'])
            ->assertJsonPath('errors.starts_at', [BookingStepValidation::ERROR_WRONG_START_TIME_STEP]);
    }

    public function test_time_is_not_on_correct_step(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => Carbon::parse('next monday')->setTime(10, 37)->toIso8601String(),
            'ends_at' => Carbon::parse('next monday')->setTime(12, 24)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at', 'ends_at'])
            ->assertJsonPath('errors.starts_at', [BookingStepValidation::ERROR_WRONG_START_TIME_STEP])
            ->assertJsonPath('errors.ends_at', [BookingStepValidation::ERROR_WRONG_END_TIME_STEP]);;
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
            'ends_at' => $start->add('4 hours 15 minute')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at'])
            ->assertJsonPath('errors.ends_at', [BookingDurationValidation::ERROR_WRONG_DURATION]);;
    }

}
