<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Training\UpdateRoutineValidator;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\UpdateRoutineUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Renaming one of the caller's routines. */
final class UpdateRoutineUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItRenamesARoutine(): void
    {
        $routine = $this->benchRoutine();

        $output = $this->useCase(UpdateRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, new UpdateRoutineDataInput('Push lourd'));

        self::assertSame('Push lourd', $output->name);
        self::assertCount(1, $output->blocks);
        self::assertSame('Push lourd', $this->reread($routine->id)->name);
    }

    public function testItRequiresAName(): void
    {
        $routine = $this->createRoutine();

        try {
            $this->useCase(UpdateRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, new UpdateRoutineDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['name' => ['name_required']], $exception->violations);
        }
    }

    public function testRenamingARoutineStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->benchRoutine()->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateRoutineUseCase::class)->execute($currentUser, $routineId, new UpdateRoutineDataInput('Push lourd')),
        );
    }
}
