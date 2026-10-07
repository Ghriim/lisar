<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\Factory\OutputFactory\Training\WorkoutCopyOutputFactory;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutCopyOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    private WorkoutCopyOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new WorkoutCopyOutputFactory(new WorkoutOutputFactory($this->mapper(), $this->setFactory()));
    }

    public function testItBuildsTheCopyAndNamesWhatWasLeftOut(): void
    {
        $workout = $this->workout(7);
        $this->set($this->exercise($this->block($workout, 1, 0), 1, 0, $this->movement(1, 'Squat')), 1, 0, 5);

        $output = $this->factory->buildOne($workout, [$this->movement(2, 'Leg extension'), $this->movement(3, 'Hack squat')]);

        self::assertSame(7, $output->workout->id);
        self::assertCount(1, $output->workout->blocks);
        self::assertSame(['Leg extension', 'Hack squat'], $output->skippedMovements);
    }

    public function testNothingLeftOutIsAnEmptyList(): void
    {
        self::assertSame([], $this->factory->buildOne($this->workout(), [])->skippedMovements);
    }
}
