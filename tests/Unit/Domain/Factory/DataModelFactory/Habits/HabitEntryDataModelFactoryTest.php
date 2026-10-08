<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\Factory\DataModelFactory\Habits\HabitEntryDataModelFactory;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class HabitEntryDataModelFactoryTest extends TestCase
{
    private HabitEntryDataModelFactory $factory;
    private HabitSubscriptionDataModel $subscription;
    private DateTimeImmutable $day;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new HabitEntryDataModelFactory();

        $habit = new HabitDataModel();
        $habit->trackerThreshold = 1500;
        $this->subscription = new HabitSubscriptionDataModel();
        $this->subscription->habit = $habit;

        $this->day = new DateTimeImmutable('2026-10-07');
        $this->now = new DateTimeImmutable('2026-10-07 14:30:00');
    }

    public function testADayThatReachesTheMarkIsKeptTheInstantItCrosses(): void
    {
        $entry = $this->build(null, 1500);

        self::assertNotNull($entry);
        self::assertTrue($entry->isCompleted);
        self::assertSame($this->now, $entry->completedAt);
        self::assertSame($this->day, $entry->day);
        self::assertSame(HabitTrackerRegistry::HYDRATION, $entry->source);
    }

    /** An empty day is an absent row: nothing to write. */
    public function testADayNeverKeptAndStillUnderTheMarkWritesNothing(): void
    {
        self::assertNull($this->build(null, 1499));
    }

    public function testADayCorrectedBelowTheMarkIsUnkept(): void
    {
        $entry = $this->build($this->kept(), 1000);

        self::assertNotNull($entry);
        self::assertFalse($entry->isCompleted);
        self::assertNull($entry->completedAt);
    }

    /** Still over the mark: the moment it first crossed stays. */
    public function testADayKeptAlreadyKeepsTheMomentItCrossed(): void
    {
        $kept = $this->kept();
        $crossedAt = $kept->completedAt;

        self::assertSame($crossedAt, $this->build($kept, 2000)?->completedAt);
    }

    public function testAHabitWithoutAMarkIsNeverKept(): void
    {
        $this->subscription->habit->trackerThreshold = null;

        self::assertNull($this->build(null, 99999));
    }

    private function build(?HabitEntryDataModel $existing, int $value): ?HabitEntryDataModel
    {
        return $this->factory->buildTrackerEntry($this->subscription, $existing, HabitTrackerRegistry::HYDRATION, $value, $this->day, $this->now);
    }

    private function kept(): HabitEntryDataModel
    {
        $entry = new HabitEntryDataModel();
        $entry->subscription = $this->subscription;
        $entry->day = $this->day;
        $entry->isCompleted = true;
        $entry->completedAt = new DateTimeImmutable('2026-10-07 09:00:00');
        $entry->source = HabitTrackerRegistry::HYDRATION;

        return $entry;
    }
}
