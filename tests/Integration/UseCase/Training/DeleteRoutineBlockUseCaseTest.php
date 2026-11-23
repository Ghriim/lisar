<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineBlockInRoutineConstraint;
use App\Domain\Validation\Validator\Training\DeleteRoutineBlockValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteRoutineBlockUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a block from a routine, with its movements and their sets. */
final class DeleteRoutineBlockUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItRemovesABlock(): void
    {
        $routine = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP);

        $output = $this->useCase(DeleteRoutineBlockUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->id);

        self::assertCount(1, $output->blocks);
        self::assertSame('Push-up', $this->reread($routine->id)->blocks[0]->exercises[0]->movement->name);
    }

    /** A block of another of the caller's routines is answered like one that does not exist. */
    public function testItRefusesABlockOfAnotherRoutine(): void
    {
        $routine = $this->createRoutine();
        $other = $this->benchRoutine('Pull');

        try {
            $this->useCase(DeleteRoutineBlockUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $other->blocks[0]->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['blockId' => [RoutineBlockInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testRemovingABlockStaysWithinItsQueryBudget(): void
    {
        $routine = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP);
        $blockId = $routine->blocks[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteRoutineBlockUseCase::class)->execute($currentUser, $routine->id, $blockId),
        );
    }
}
