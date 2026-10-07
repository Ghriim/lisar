<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\DataModelFactory\Training\WorkoutDataModelFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkoutDataModelFactoryTest extends TestCase
{
    private WorkoutDataModelFactory $factory;
    private MovementFamilyDataModel $family;
    private WorkoutDataModel $source;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new WorkoutDataModelFactory();
        $this->family = new MovementFamilyDataModel();
        $this->now = new DateTimeImmutable('2026-10-07T18:00:00+00:00');

        $owner = new UserDataModel();
        $owner->id = 1;

        $this->source = new WorkoutDataModel();
        $this->source->owner = $owner;
        $this->source->name = 'Push';
        $this->source->note = 'Grosse forme';
        $this->source->feeling = 4;
        $this->source->startedAt = new DateTimeImmutable('2026-10-05T18:00:00+00:00');
        $this->source->finishedAt = new DateTimeImmutable('2026-10-05T19:00:00+00:00');
    }

    public function testItLaysTheCopyOutLikeTheSourceWithItsSetsStillToDo(): void
    {
        $bench = $this->movement('Bench press', reps: true, weight: true);
        $setType = new SetTypeDataModel();
        $exercise = $this->exercise($this->block(0), $bench, 0, 'Prise serrée');
        $this->set($exercise, 0, reps: 10, weight: 40.0, setType: $setType);
        $this->set($exercise, 1, reps: 8, weight: 60.0, rpe: 8.5);

        $copy = $this->factory->buildCopy($this->source, $this->now);

        self::assertSame($this->source->owner, $copy->owner);
        self::assertSame('Push', $copy->name);
        // The day it was done stays behind: its note, its feeling, its moments.
        self::assertNull($copy->note);
        self::assertNull($copy->feeling);
        self::assertSame($this->now, $copy->startedAt);
        self::assertTrue($copy->isInProgress());

        $copiedExercise = $copy->orderedBlocks()[0]->orderedExercises()[0];
        self::assertSame($bench, $copiedExercise->movement);
        self::assertSame('Prise serrée', $copiedExercise->note);

        $sets = $copiedExercise->orderedSets();
        self::assertSame([10, 8], array_map(static fn (WorkoutSetDataModel $set) => $set->reps, $sets));
        self::assertSame([40.0, 60.0], array_map(static fn (WorkoutSetDataModel $set) => $set->weightInKilograms, $sets));
        self::assertSame($setType, $sets[0]->setType);
        self::assertSame(8.5, $sets[1]->rpe);
        self::assertSame(0, $copy->countSets() - $copy->countIncompleteSets());
    }

    public function testItKeepsTheOrderOfBlocksAndOfASuperset(): void
    {
        $this->exercise($this->block(5), $this->movement('Dips'), 0);
        $superset = $this->block(2);
        $this->exercise($superset, $this->movement('Push-up'), 3);
        $this->exercise($superset, $this->movement('Row'), 1);

        $copy = $this->factory->buildCopy($this->source, $this->now);

        self::assertSame([['Row', 'Push-up'], ['Dips']], $this->layout($copy));
    }

    public function testItLeavesOutWhatIsNoLongerOfferedAndTheBlockItEmpties(): void
    {
        $retired = $this->movement('Leg extension');
        $retired->isActive = false;
        $retiredFamily = new MovementFamilyDataModel();
        $retiredFamily->isActive = false;
        $inRetiredFamily = $this->movement('Hack squat');
        $inRetiredFamily->movementFamily = $retiredFamily;

        $this->exercise($this->block(0), $retired, 0);
        $superset = $this->block(1);
        $this->exercise($superset, $this->movement('Squat'), 0);
        $this->exercise($superset, $inRetiredFamily, 1);
        $this->exercise($this->block(2), $retired, 0);

        $copy = $this->factory->buildCopy($this->source, $this->now);

        self::assertSame([['Squat']], $this->layout($copy));
        self::assertSame([$retired, $inRetiredFamily], $this->factory->movementsLeftOut($this->source));
    }

    public function testASetTypeRetiredSinceIsDropped(): void
    {
        $setType = new SetTypeDataModel();
        $setType->isActive = false;
        $this->set($this->exercise($this->block(0), $this->movement('Squat'), 0), 0, reps: 5, setType: $setType);

        $copy = $this->factory->buildCopy($this->source, $this->now);

        self::assertNull($copy->orderedBlocks()[0]->orderedExercises()[0]->orderedSets()[0]->setType);
    }

    /** The movement now tracks a weight, no longer a duration: one set still fits, one does not. */
    public function testASetFollowsWhatItsMovementTracksNow(): void
    {
        $movement = $this->movement('Plank', reps: true, weight: true);
        $exercise = $this->exercise($this->block(0), $movement, 0);
        $this->set($exercise, 0, reps: 10, weight: 20.0, duration: 30);
        $this->set($exercise, 1, reps: 10);

        $copy = $this->factory->buildCopy($this->source, $this->now);

        $sets = $copy->orderedBlocks()[0]->orderedExercises()[0]->orderedSets();
        self::assertCount(1, $sets);
        self::assertSame(20.0, $sets[0]->weightInKilograms);
        self::assertNull($sets[0]->durationInSeconds);
    }

    public function testNothingIsLeftOutWhenEverythingIsOffered(): void
    {
        $this->exercise($this->block(0), $this->movement('Squat'), 0);

        self::assertSame([], $this->factory->movementsLeftOut($this->source));
    }

    /** @return list<list<string>> */
    private function layout(WorkoutDataModel $workout): array
    {
        return array_map(
            static fn (WorkoutBlockDataModel $block) => array_map(static fn (WorkoutExerciseDataModel $exercise) => $exercise->movement->name, $block->orderedExercises()),
            $workout->orderedBlocks(),
        );
    }

    private function movement(string $name, bool $reps = true, bool $weight = false): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->name = $name;
        $movement->movementFamily = $this->family;
        $movement->tracksReps = $reps;
        $movement->tracksWeight = $weight;

        return $movement;
    }

    private function block(int $position): WorkoutBlockDataModel
    {
        $block = new WorkoutBlockDataModel();
        $block->id = $position + 1;
        $block->position = $position;
        $block->workout = $this->source;
        $this->source->blocks->add($block);

        return $block;
    }

    private function exercise(WorkoutBlockDataModel $block, MovementDataModel $movement, int $position, ?string $note = null): WorkoutExerciseDataModel
    {
        $exercise = new WorkoutExerciseDataModel();
        $exercise->position = $position;
        $exercise->movement = $movement;
        $exercise->note = $note;
        $exercise->block = $block;
        $block->exercises->add($exercise);

        return $exercise;
    }

    private function set(
        WorkoutExerciseDataModel $exercise,
        int $position,
        ?int $reps = null,
        ?float $weight = null,
        ?int $duration = null,
        ?float $rpe = null,
        ?SetTypeDataModel $setType = null,
    ): void {
        $set = new WorkoutSetDataModel();
        $set->position = $position;
        $set->reps = $reps;
        $set->weightInKilograms = $weight;
        $set->durationInSeconds = $duration;
        $set->rpe = $rpe;
        $set->setType = $setType;
        $set->isComplete = true;
        $set->exercise = $exercise;
        $exercise->sets->add($set);
    }
}
