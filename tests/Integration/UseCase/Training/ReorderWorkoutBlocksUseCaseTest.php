<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\ReorderWorkoutBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutBlockOrderConstraint;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\ReorderWorkoutBlocksUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Putting the blocks of a workout in a new order. */
final class ReorderWorkoutBlocksUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItReordersTheBlocks(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        $output = $this->useCase(ReorderWorkoutBlocksUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id,
            new ReorderWorkoutBlocksDataInput([$workout->blocks[1]->id, $workout->blocks[0]->id]),
        );

        self::assertSame(['Push-up'], $this->movementNames($output, 0));
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
        self::assertSame(['Push-up'], $this->movementNames($reread, 0));
    }

    public function testANewOrderNamesEveryBlockOnce(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        try {
            $this->useCase(ReorderWorkoutBlocksUseCase::class)->execute(
                $this->currentUser($this->idOf($this->alice)), $workout->id, new ReorderWorkoutBlocksDataInput([$workout->blocks[1]->id]),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([WorkoutBlockOrderConstraint::MISMATCH], $exception->violations['blockIds']);
        }
    }

    public function testReorderingTheBlocksStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $blockIds = array_reverse(array_map(static fn ($block) => $block->id, $workout->blocks));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ReorderWorkoutBlocksUseCase::class)->execute($currentUser, $workout->id, new ReorderWorkoutBlocksDataInput($blockIds)),
        );
    }
}
