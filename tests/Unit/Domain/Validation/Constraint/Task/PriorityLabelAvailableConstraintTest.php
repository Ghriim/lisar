<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Validation\Constraint\Task\PriorityLabelAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class PriorityLabelAvailableConstraintTest extends TestCase
{
    public function testItAcceptsAFreeLabel(): void
    {
        self::assertSame([], PriorityLabelAvailableConstraint::validate(null));
    }

    public function testItRejectsALabelAlreadyCarried(): void
    {
        self::assertSame(
            ['label' => [PriorityLabelAvailableConstraint::LABEL_ALREADY_USED]],
            PriorityLabelAvailableConstraint::validate($this->buildPriority(1)),
        );
    }

    public function testAPriorityMayKeepItsOwnLabel(): void
    {
        self::assertSame([], PriorityLabelAvailableConstraint::validate($this->buildPriority(7), 7));
    }

    public function testItStillRejectsAnotherPrioritysLabel(): void
    {
        self::assertSame(
            ['label' => [PriorityLabelAvailableConstraint::LABEL_ALREADY_USED]],
            PriorityLabelAvailableConstraint::validate($this->buildPriority(7), 9),
        );
    }

    private function buildPriority(int $id): PriorityDataModel
    {
        $priority = new PriorityDataModel();
        $priority->id = $id;
        $priority->label = 'High';

        return $priority;
    }
}
