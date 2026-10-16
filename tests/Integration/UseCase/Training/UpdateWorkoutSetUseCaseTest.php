<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a set: its measures and its type. Its handlers move its workout's summary and the records. */
final class UpdateWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** No type named: the set takes the default one, at logging and at a correction alike. */
    public function testASetWithoutATypeTakesTheDefault(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10));
        $set = $workout->blocks[0]->exercises[0]->sets[0];
        self::assertSame('Travail', $set->setType->name);
        self::assertTrue($set->setType->isDefaultType);

        $workout = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $set->id,
            new UpdateWorkoutSetDataInput(reps: 10, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)),
        );
        $output = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $set->id, new UpdateWorkoutSetDataInput(reps: 10),
        );

        self::assertSame('Travail', $output->blocks[0]->exercises[0]->sets[0]->setType->name);
    }

    /** Staying is not taking on: a set keeps a set type retired since, through a correction. */
    public function testACorrectedSetKeepsASetTypeRetiredSince(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)));
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->currentAdmin(), $this->setTypeId(SetTypeFixtures::DROPSET));

        $output = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->exercises[0]->sets[0]->id,
            new UpdateWorkoutSetDataInput(reps: 12, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)),
        );

        $set = $output->blocks[0]->exercises[0]->sets[0];
        self::assertSame(12, $set->reps);
        self::assertSame('Dropset', $set->setType->name);
    }

    /** The column keeps what it is handed: the use case is what keeps two decimals of a load. */
    public function testItKeepsTwoDecimalsOfACorrectedLoad(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 60));

        $output = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->exercises[0]->sets[0]->id,
            new UpdateWorkoutSetDataInput(reps: 8, weightInKilograms: 62.454),
        );

        self::assertSame(62.45, $output->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    /** A correction replaces the measures, not whether the set was done. */
    public function testACorrectedSetStaysTicked(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $output = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->exercises[0]->sets[0]->id, new UpdateWorkoutSetDataInput(reps: 12),
        );

        self::assertTrue($output->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    public function testCorrectingASetRaisesItsEvent(): void
    {
        $workout = $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        $this->forgetRaisedEvents();

        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 10, weightInKilograms: 50.0),
        );

        $event = $this->assertEventRaised(WorkoutSetUpdatedEvent::class);
        self::assertSame($this->lastSet($workout)->id, $event->set->id);
        self::assertSame(10, $event->set->reps);
        self::assertTrue($event->countedBefore);
    }

    public function testUpdatingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId, new UpdateWorkoutSetDataInput(reps: 6, weightInKilograms: 72.5)),
        );
    }
}
