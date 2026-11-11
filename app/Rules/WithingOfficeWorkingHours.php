<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class WithingOfficeWorkingHours implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function __construct(
        protected string $officeTimezone, // Например, 'Europe/Moscow' или 'Asia/Yekaterinburg'
        protected string $startField = 'starts_at',
        protected string $endField = 'ends_at'
    )
    {
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Если даты еще не прошли базовую валидацию формата, выходим
        if (empty($this->data[$this->startField]) || empty($this->data[$this->endField])) {
            return;
        }



        if (!$start->isSameDay($end)) {
            $fail('Бронь должна начинаться и заканчиваться в пределах одного рабочего дня офиса.');
            return;
        }

        // Задаем границы рабочего дня офиса для даты начала брони
        $workStart = $start->copy()->setTime(9, 0, 0);
        $workEnd = $start->copy()->setTime(20, 0, 0);

        if ($start->lt($workStart) || $end->gt($workEnd)) {
            $fail("Бронь должна укладываться в рабочее время офиса (09:00–20:00 {$this->officeTimezone}).");
        }
    }
}
