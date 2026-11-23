<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\ReorderRoutineBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineBlockOrderConstraint;
use App\Domain\Validation\Validator\Training\ReorderRoutineBlocksValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\ReorderRoutineBlocksUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Putting a routine's blocks in a new order. */
final class ReorderRoutineBlocksUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItReordersTheBlocks(): void
    {
        $routine = $this->addRoutineBlock($this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL), MovementFixtures::PUSH_UP);

        $output = $this->useCase(ReorderRoutineBlocksUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, new ReorderRoutineBlocksDataInput([$routine->blocks[1]->id, $routine->blocks[0]->id]));

        self::assertSame('Push-up', $output->blocks[0]->exercises[0]->movement->name);
        self::assertSame('Push-up', $this->reread($routine->id)->blocks[0]->exercises[0]->movement->name);
    }

    public function testItRefusesAListThatIsNotTheRoutinesBlocks(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        try {
            $this->useCase(ReorderRoutineBlocksUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, new ReorderRoutineBlocksDataInput([]));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReorderRoutineBlocksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['blockIds' => [RoutineBlockOrderConstraint::MISMATCH]], $exception->violations);
        }
    }

    public function testReorderingTheBlocksStaysWithinItsQueryBudget(): void
    {
        $routine = $this->addRoutineBlock($this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL), MovementFixtures::PUSH_UP);
        $order = [$routine->blocks[1]->id, $routine->blocks[0]->id];

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ReorderRoutineBlocksUseCase::class)->execute($currentUser, $routine->id, new ReorderRoutineBlocksDataInput($order)),
        );
    }
}
