<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Hydration;

use App\Domain\Validation\Constraint\Hydration\EntryFromTodayConstraint;
use PHPUnit\Framework\TestCase;

final class EntryFromTodayConstraintTest extends TestCase
{
    public function testTodaysEntryMayBeTouched(): void
    {
        self::assertSame([], EntryFromTodayConstraint::validate(true));
    }

    public function testAnEntryFromAPastDayMayNot(): void
    {
        self::assertSame(
            ['id' => [EntryFromTodayConstraint::ENTRY_NOT_FROM_TODAY]],
            EntryFromTodayConstraint::validate(false),
        );
    }
}
