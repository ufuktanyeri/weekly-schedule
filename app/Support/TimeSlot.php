<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable [start, end) time range expressed as "HH:MM" strings.
 */
final readonly class TimeSlot
{
    public int $startMinutes;

    public int $endMinutes;

    public function __construct(
        public string $start,
        public string $end,
    ) {
        $this->startMinutes = self::toMinutes($start);
        $this->endMinutes = self::toMinutes($end);

        if ($this->endMinutes <= $this->startMinutes) {
            throw new InvalidArgumentException("Time slot end [{$end}] must be after start [{$start}].");
        }
    }

    public static function toMinutes(string $time): int
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m) !== 1) {
            throw new InvalidArgumentException("Invalid time [{$time}], expected HH:MM.");
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }

    public static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function overlaps(self $other): bool
    {
        return $this->startMinutes < $other->endMinutes && $other->startMinutes < $this->endMinutes;
    }

    public function durationMinutes(): int
    {
        return $this->endMinutes - $this->startMinutes;
    }

    public function label(): string
    {
        return $this->start.'-'.$this->end;
    }
}
