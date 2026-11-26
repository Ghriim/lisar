<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Todo;

use App\Domain\Factory\DataModelFactory\Todo\TodoDataModelFactory;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class TodoDataModelFactoryTest extends TestCase
{
    use CurrentUserTrait;

    public function testTheDefaultIsAFaireActiveAndDefault(): void
    {
        $owner = $this->anyCurrentUser();
        $todo = (new TodoDataModelFactory())->buildDefault($owner);

        self::assertSame($owner, $todo->owner);
        self::assertSame(['A faire', TodoStatusRegistry::ACTIVE, true], [$todo->title, $todo->status, $todo->isDefault]);
    }

    /** The title is stored trimmed: as the uniqueness compares it. */
    public function testAnOrdinaryTodoIsActiveWithItsTitleTrimmed(): void
    {
        $todo = (new TodoDataModelFactory())->build($this->anyCurrentUser(), '  Maison  ');

        self::assertSame(['Maison', TodoStatusRegistry::ACTIVE, false], [$todo->title, $todo->status, $todo->isDefault]);
    }
}
