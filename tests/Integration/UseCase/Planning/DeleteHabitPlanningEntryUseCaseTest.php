<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\HabitPlanningEntryNotLastConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\CreateHabitPlanningEntryUseCase;
use App\UseCase\Planning\DeleteHabitPlanningEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A habit entry deleted: its dates to come go, the days the habit was kept stay. */
final class DeleteHabitPlanningEntryUseCaseTest extends KernelTestCase
{
    use HabitPlanningTestTrait;
    use QueryCountTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItDeletesAnEntry(): void
    {
        $weekends = $this->secondEntryForReading();

        $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());

        self::assertNull(self::getContainer()->get(HabitPlanningEntryProviderGateway::class)->findOneById($this->seededEntryId()));
        $reading = array_filter($this->thisWeek()->entries, fn ($entry): bool => $this->readingId() === $entry->habitId);
        self::assertSame([$weekends], array_values(array_map(static fn ($entry): int => $entry->id, $reading)));
    }

    /** Reading on Mondays, Wednesdays and Fridays is all its planning: it is changed, not deleted. */
    public function testTheLastEntryOfAHabitIsRefused(): void
    {
        try {
            $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['id' => [HabitPlanningEntryNotLastConstraint::LAST_HABIT_PLANNING_ENTRY]], $exception->violations);
        }

        self::assertNotNull(self::getContainer()->get(HabitPlanningEntryProviderGateway::class)->findOneById($this->seededEntryId()));
    }

    public function testSomeoneElsesEntryIsRefused(): void
    {
        try {
            $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->bobId), $this->seededEntryId());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), 123456789);
    }

    public function testDeletingStaysWithinItsQueryBudget(): void
    {
        $this->secondEntryForReading();
        $entryId = $this->seededEntryId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($currentUser, $entryId),
            // The entry with its subscription and habit, the habit's other entries, then the DELETE.
            max: 3,
        );
    }

    /** Reading on weekends as well, so that the seeded entry is not its last: the new entry's id. */
    private function secondEntryForReading(): int
    {
        return $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->weekly($this->readingId(), [6, 7]))->id;
    }
}
