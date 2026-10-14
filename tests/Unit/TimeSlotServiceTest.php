<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TimeSlotService;
use App\Support\TimeSlot;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TimeSlotServiceTest extends TestCase
{
    public function test_day_is_split_into_30_minute_slots_from_0830_to_1800(): void
    {
        $slots = (new TimeSlotService)->allSlots();

        $this->assertCount(19, $slots);
        $this->assertSame('08:30-09:00', $slots[0]->label());
        $this->assertSame('09:00-09:30', $slots[1]->label());
        $this->assertSame('17:30-18:00', $slots[18]->label());
    }

    public function test_lunch_break_slots_are_excluded_from_teaching_slots(): void
    {
        $service = new TimeSlotService;
        $labels = array_map(fn (TimeSlot $s): string => $s->label(), $service->teachingSlots());

        $this->assertCount(16, $labels);
        $this->assertContains('12:00-12:30', $labels);
        $this->assertNotContains('12:30-13:00', $labels);
        $this->assertNotContains('13:00-13:30', $labels);
        $this->assertNotContains('13:30-14:00', $labels);
        $this->assertContains('14:00-14:30', $labels);
    }

    public function test_lunch_break_is_configurable(): void
    {
        $disabled = new TimeSlotService(lunchEnabled: false);
        $this->assertCount(19, $disabled->teachingSlots());
        $this->assertNull($disabled->lunchBreak());

        $custom = new TimeSlotService(lunchStart: '12:00', lunchEnd: '13:00');
        $this->assertTrue($custom->isLunch(new TimeSlot('12:00', '12:30')));
        $this->assertFalse($custom->isLunch(new TimeSlot('13:00', '13:30')));
        $this->assertCount(17, $custom->teachingSlots());
    }

    public function test_between_splits_a_range_into_grid_slots(): void
    {
        $slots = (new TimeSlotService)->between('09:00', '11:00');

        $this->assertSame(
            ['09:00-09:30', '09:30-10:00', '10:00-10:30', '10:30-11:00'],
            array_map(fn (TimeSlot $s): string => $s->label(), $slots),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidRanges(): array
    {
        return [
            'misaligned start' => ['09:15', '10:00'],
            'misaligned end' => ['09:00', '09:45'],
            'before day start' => ['08:00', '09:00'],
            'after day end' => ['17:30', '18:30'],
            'end before start' => ['10:00', '09:00'],
            'malformed' => ['9:00', '10:00'],
        ];
    }

    #[DataProvider('invalidRanges')]
    public function test_between_rejects_invalid_ranges(string $start, string $end): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TimeSlotService)->between($start, $end);
    }

    public function test_grid_slot_detection(): void
    {
        $service = new TimeSlotService;

        $this->assertTrue($service->isGridSlot(new TimeSlot('08:30', '09:00')));
        $this->assertFalse($service->isGridSlot(new TimeSlot('08:45', '09:15')));
        $this->assertFalse($service->isGridSlot(new TimeSlot('08:30', '09:30')));
    }

    public function test_time_slot_overlap(): void
    {
        $a = new TimeSlot('09:00', '10:00');

        $this->assertTrue($a->overlaps(new TimeSlot('09:30', '10:30')));
        $this->assertFalse($a->overlaps(new TimeSlot('10:00', '10:30')));
        $this->assertSame(60, $a->durationMinutes());
    }
}
