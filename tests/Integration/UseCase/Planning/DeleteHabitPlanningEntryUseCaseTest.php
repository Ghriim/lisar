<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
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
        $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());

        self::assertNull(self::getContainer()->get(HabitPlanningEntryProviderGateway::class)->findOneById($this->seededEntryId()));
        self::assertSame([], $this->thisWeek()->entries);
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
        $entryId = $this->seededEntryId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($currentUser, $entryId),
            // The entry with its subscription and habit, then the DELETE.
            max: 2,
        );
    }
}
