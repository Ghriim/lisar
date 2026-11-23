<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineSetInRoutineConstraint;
use App\Domain\Validation\Validator\Training\UpdateRoutineSetValidator;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\UpdateRoutineSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a prescribed set, its targets replaced whole. */
final class UpdateRoutineSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItCorrectsASet(): void
    {
        $routine = $this->benchRoutine();

        $output = $this->useCase(UpdateRoutineSetUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->exercises[0]->sets[0]->id, new UpdateRoutineSetDataInput(3, 87.5));

        self::assertSame([3, 87.5], [$output->blocks[0]->exercises[0]->sets[0]->reps, $output->blocks[0]->exercises[0]->sets[0]->percentageOfOneRepMax]);
        self::assertSame(87.5, $this->reread($routine->id)->blocks[0]->exercises[0]->sets[0]->percentageOfOneRepMax);
    }

    public function testItRefusesASetOfAnotherRoutine(): void
    {
        $routine = $this->createRoutine();
        $other = $this->benchRoutine('Pull');

        try {
            $this->useCase(UpdateRoutineSetUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $other->blocks[0]->exercises[0]->sets[0]->id, new UpdateRoutineSetDataInput(3, 80.0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['setId' => [RoutineSetInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testUpdatingASetStaysWithinItsQueryBudget(): void
    {
        $routine = $this->benchRoutine();
        $setId = $routine->blocks[0]->exercises[0]->sets[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateRoutineSetUseCase::class)->execute($currentUser, $routine->id, $setId, new UpdateRoutineSetDataInput(3, 87.5)),
        );
    }
}
