<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a set: its measures and its type; its workout's summary and the records follow. */
final class UpdateWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

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

    public function testCorrectingASetDownHandsTheRecordBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 5, weightInKilograms: 50.0),
        );

        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testCorrectingADoneSetMovesTheTotals(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 10, weightInKilograms: 50.0),
        );

        self::assertSame(500.0, (float) $this->summaryOf($workout->id)->volumeInKilograms);
    }

    /**
     * A record taken back by an older workout leaves the newer one: its count moves although none
     * of its own sets did.
     */
    public function testARecordTakenBackByAnotherWorkoutLeavesTheCount(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));
        $before = $this->summaryOf($workout->id)->personalBestCount;
        self::assertGreaterThan(0, $before);

        // The seeded workout's heaviest bench set, corrected up past the new one.
        $seededId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;
        $seeded = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $seededId);
        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $seededId, $seeded->blocks[0]->exercises[0]->sets[1]->id, new UpdateWorkoutSetDataInput(reps: 8, weightInKilograms: 90.0),
        );

        self::assertLessThan($before, $this->summaryOf($workout->id)->personalBestCount);
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
