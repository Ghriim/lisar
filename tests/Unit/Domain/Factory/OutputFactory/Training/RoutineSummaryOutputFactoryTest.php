<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\Factory\OutputFactory\Training\RoutineSummaryOutputFactory;
use App\Domain\Training\RoutineTree;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class RoutineSummaryOutputFactoryTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    /** A movement that comes twice counts twice: it is what the routine's page lists. */
    public function testItCountsTheMovementsOfEachRoutine(): void
    {
        $push = $this->routineOf($this->anyCurrentUser());
        $push->id = 1;
        $block = $this->routineBlockIn($push);
        $this->routineExerciseIn($block, $this->barbellMovement(1));
        $this->routineExerciseIn($this->routineBlockIn($push, 1), $this->barbellMovement(1));
        $empty = $this->routineOf($this->anyCurrentUser());
        $empty->id = 2;
        $empty->name = 'Pull';

        $outputs = (new RoutineSummaryOutputFactory(new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor()), new RoutineTree()))->buildMany([$push, $empty]);

        self::assertSame([1, 'Push', 2], [$outputs[0]->id, $outputs[0]->name, $outputs[0]->movementCount]);
        self::assertSame([2, 'Pull', 0], [$outputs[1]->id, $outputs[1]->name, $outputs[1]->movementCount]);
    }
}
