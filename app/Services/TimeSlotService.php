<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\TimeSlot;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

/**
 * Generates the fixed-length time slots of the weekly grid and knows
 * which of them fall into the (configurable) lunch break.
 */
final class TimeSlotService
{
    private readonly int $dayStart;

    private readonly int $dayEnd;

    private readonly ?TimeSlot $lunchBreak;

    public function __construct(
        string $dayStart = '08:30',
        string $dayEnd = '18:00',
        private readonly int $slotMinutes = 30,
        bool $lunchEnabled = true,
        string $lunchStart = '12:30',
        string $lunchEnd = '14:00',
    ) {
        $this->dayStart = TimeSlot::toMinutes($dayStart);
        $this->dayEnd = TimeSlot::toMinutes($dayEnd);

        if ($slotMinutes <= 0 || $this->dayEnd <= $this->dayStart || ($this->dayEnd - $this->dayStart) % $slotMinutes !== 0) {
            throw new InvalidArgumentException('The teaching day must be divisible into whole slots.');
        }

        $this->lunchBreak = $lunchEnabled ? new TimeSlot($lunchStart, $lunchEnd) : null;
    }

    public static function fromConfig(Repository $config): self
    {
        return new self(
            dayStart: (string) $config->get('schedule.day_start', '08:30'),
            dayEnd: (string) $config->get('schedule.day_end', '18:00'),
            slotMinutes: (int) $config->get('schedule.slot_minutes', 30),
            lunchEnabled: (bool) $config->get('schedule.lunch_break.enabled', true),
            lunchStart: (string) $config->get('schedule.lunch_break.start', '12:30'),
            lunchEnd: (string) $config->get('schedule.lunch_break.end', '14:00'),
        );
    }

    public function slotMinutes(): int
    {
        return $this->slotMinutes;
    }

    public function lunchBreak(): ?TimeSlot
    {
        return $this->lunchBreak;
    }

    /**
     * Every slot of the day, including the ones inside the lunch break.
     *
     * @return list<TimeSlot>
     */
    public function allSlots(): array
    {
        $slots = [];

        for ($t = $this->dayStart; $t < $this->dayEnd; $t += $this->slotMinutes) {
            $slots[] = new TimeSlot(TimeSlot::fromMinutes($t), TimeSlot::fromMinutes($t + $this->slotMinutes));
        }

        return $slots;
    }

    /**
     * Slots that can be used for teaching (lunch break excluded).
     *
     * @return list<TimeSlot>
     */
    public function teachingSlots(): array
    {
        return array_values(array_filter(
            $this->allSlots(),
            fn (TimeSlot $slot): bool => ! $this->isLunch($slot),
        ));
    }

    public function isLunch(TimeSlot $slot): bool
    {
        return $this->lunchBreak !== null && $slot->overlaps($this->lunchBreak);
    }

    /**
     * Whether the slot is exactly one of the grid slots.
     */
    public function isGridSlot(TimeSlot $slot): bool
    {
        return $slot->durationMinutes() === $this->slotMinutes
            && $slot->startMinutes >= $this->dayStart
            && $slot->endMinutes <= $this->dayEnd
            && ($slot->startMinutes - $this->dayStart) % $this->slotMinutes === 0;
    }

    /**
     * Split the [start, end) range into grid slots.
     *
     * @return non-empty-list<TimeSlot>
     *
     * @throws InvalidArgumentException when the range is not aligned to the grid
     */
    public function between(string $start, string $end): array
    {
        $range = new TimeSlot($start, $end);

        if ($range->startMinutes < $this->dayStart
            || $range->endMinutes > $this->dayEnd
            || ($range->startMinutes - $this->dayStart) % $this->slotMinutes !== 0
            || $range->durationMinutes() % $this->slotMinutes !== 0) {
            throw new InvalidArgumentException(sprintf(
                '%s aralığı %s-%s arasındaki %d dakikalık dilimlere uymuyor.',
                $range->label(),
                TimeSlot::fromMinutes($this->dayStart),
                TimeSlot::fromMinutes($this->dayEnd),
                $this->slotMinutes,
            ));
        }

        $slots = [];
        $t = $range->startMinutes;
        do {
            $slots[] = new TimeSlot(TimeSlot::fromMinutes($t), TimeSlot::fromMinutes($t + $this->slotMinutes));
            $t += $this->slotMinutes;
        } while ($t < $range->endMinutes);

        return $slots;
    }
}
