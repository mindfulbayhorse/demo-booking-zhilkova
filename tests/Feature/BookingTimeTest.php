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
        $this->office = Office::factory()->create(['timezone' => 'Europe/Moscow']);
        $this->room = Room::factory()->for($this->office)->create();
        $this->start = Carbon::parse('next monday')->setTimeZone('Europe/Moscow');
    }

    public function test_booking_starts_outside_of_office_working_hours(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $this->start->setTime(5, 0)->toIso8601String(),
            'ends_at' => $this->start->add('4 hours')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.booking_time', [BookingWorkingTimeValidation::ERROR_OUTSIDE_WORKING_HOURS . " ({$this->room->office->timezone})"]);
    }

    public function test_booking_ends_outside_of_working_hours(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $this->start->setTime(8, 0)->toIso8601String(),
            'ends_at' => $this->start->setTime(21, 0)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.booking_time', [BookingWorkingTimeValidation::ERROR_OUTSIDE_WORKING_HOURS . " ({$this->room->office->timezone})"]);

    }

    public function test_booking_is_outside_of_working_hours(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $this->start->setTime(3, 0)->toIso8601String(),
            'ends_at' => $this->start->setTime(20, 0)->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.booking_time', [BookingWorkingTimeValidation::ERROR_OUTSIDE_WORKING_HOURS . " ({$this->room->office->timezone})"]);;
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

    public function test_booking_has_incorrect_duration(): void
    {
        $start = $startsAt = Carbon::parse('next monday')->setTime(10, 0)->setTimezone($this->office->timezone);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/bookings', [
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $start->add('4 hours 15 minute')->toIso8601String(),
            'room_id' => $this->room->id,
            'title' => 'Test booking',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.booking_time', [BookingDurationValidation::ERROR_WRONG_DURATION]);
    }

}
