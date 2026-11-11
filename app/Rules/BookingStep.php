<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BookingStep implements ValidationRule
{
    /**
     * @var int step of the booking in minutes
     */
    protected int $minStep = 15;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $time = Carbon::parse($value);
        if ($time->minute % $this->minStep !== 0 || $time->second !== 0) {
            $fail(':attribute должно быть кратно шагу в '.$this->minStep.' минут');
        }
    }
}
