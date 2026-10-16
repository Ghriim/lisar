<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutCreatedEvent;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\Training\StartWorkoutValidator;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Starting a workout: empty, in progress, one at a time. */
final class StartWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItStartsAnEmptyWorkoutInProgress(): void
    {
        $output = $this->start('Jambes');

        self::assertSame('Jambes', $output->name);
        self::assertTrue($output->isInProgress);
        self::assertNull($output->finishedAt);
        self::assertSame([], $output->blocks);

        self::assertSame($output->id, $this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)))?->id);
    }

    /** A blank name is no name: each front end words its own default. */
    public function testABlankNameIsNone(): void
    {
        self::assertNull($this->start('')->name);
    }

    public function testItRefusesASecondWorkoutInProgress(): void
    {
        $this->start();

        try {
            $this->start();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(StartWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS], $exception->violations['workout']);
        }
    }

    /** Its summary is its handler's to create. */
    public function testStartingAWorkoutRaisesItsEvent(): void
    {
        $output = $this->start();

        self::assertSame($output->id, $this->assertEventRaised(WorkoutCreatedEvent::class)->workout->id);
    }

    public function testStartingAWorkoutStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(StartWorkoutUseCase::class)->execute($currentUser, new StartWorkoutDataInput('Jambes')),
        );
    }
}
