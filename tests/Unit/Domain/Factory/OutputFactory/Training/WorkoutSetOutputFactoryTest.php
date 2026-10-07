<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use PHPUnit\Framework\TestCase;

final class WorkoutSetOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    public function testItBuildsAnOrdinarySet(): void
    {
        $set = new WorkoutSetDataModel();
        $set->id = 5;
        $set->reps = 8;
        $set->weightInKilograms = 62.5;
        $set->isComplete = true;
        $set->setType = $this->workingSetType();

        $output = $this->setFactory()->buildOne($set);

        self::assertSame(5, $output->id);
        self::assertSame(8, $output->reps);
        self::assertSame(62.5, $output->weightInKilograms);
        self::assertNull($output->rpe);
        self::assertSame('Travail', $output->setType->name);
        self::assertTrue($output->setType->isDefaultType);
        self::assertTrue($output->isComplete);
    }

    public function testItNestsTheSetType(): void
    {
        $setType = new SetTypeDataModel();
        $setType->id = 2;
        $setType->name = 'Dropset';
        $setType->colour = SetTypeColourRegistry::PURPLE;

        $set = new WorkoutSetDataModel();
        $set->id = 5;
        $set->setType = $setType;

        $output = $this->setFactory()->buildOne($set);

        self::assertSame('Dropset', $output->setType->name);
        self::assertFalse($output->setType->isDefaultType);
        self::assertSame(SetTypeColourRegistry::PURPLE, $output->setType->colour);
    }
}
