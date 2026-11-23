<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\TrainingPlanningEntryProviderGateway;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\DeleteTrainingPlanningEntryUseCase;
use App\UseCase\Training\DeleteRoutineUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A training entry gone, its dates to come with it. */
final class DeleteTrainingPlanningEntryUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use TrainingPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItDeletesAnEntry(): void
    {
        $this->useCase(DeleteTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());

        self::assertNull(self::getContainer()->get(TrainingPlanningEntryProviderGateway::class)->findOneById($this->seededEntryId()));
        self::assertSame([], $this->thisWeek()->entries);
    }

    /** A routine deleted takes its entries with it. */
    public function testDeletingTheRoutineDeletesItsEntries(): void
    {
        $this->useCase(DeleteRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $this->pushId());

        self::assertSame([], $this->thisWeek()->entries);
    }

    public function testSomeoneElsesEntryIsRefused(): void
    {
        try {
            $this->useCase(DeleteTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->bobId), $this->seededEntryId());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(DeleteTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), 123456789);
    }

    public function testDeletingStaysWithinItsQueryBudget(): void
    {
        $entryId = $this->seededEntryId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteTrainingPlanningEntryUseCase::class)->execute($currentUser, $entryId),
            max: 2,
        );
    }
}
