<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Training\CreateRoutineValidator;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\CreateRoutineUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A new routine, empty, named, of the caller's own. */
final class CreateRoutineUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItCreatesAnEmptyRoutine(): void
    {
        $output = $this->createRoutine('Push');

        self::assertSame('Push', $output->name);
        self::assertSame([], $output->blocks);
        self::assertSame('Push', $this->reread($output->id)->name);
    }

    public function testItRequiresAName(): void
    {
        try {
            $this->useCase(CreateRoutineUseCase::class)->execute($this->currentUser($this->aliceId), new CreateRoutineDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['name' => ['name_required']], $exception->violations);
        }
    }

    public function testCreatingARoutineStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(CreateRoutineUseCase::class)->execute($currentUser, new CreateRoutineDataInput('Push')),
        );
    }
}
