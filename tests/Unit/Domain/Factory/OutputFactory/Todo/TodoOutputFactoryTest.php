<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Todo;

use App\Domain\Factory\OutputFactory\Todo\TodoOutputFactory;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Tests\CurrentUserTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class TodoOutputFactoryTest extends TestCase
{
    use CurrentUserTrait;

    public function testItCarriesEveryField(): void
    {
        $todo = $this->todoOf($this->anyCurrentUser(), 3, TodoStatusRegistry::ARCHIVED, true);
        $todo->createdAt = new DateTimeImmutable('2026-11-26 10:00:00');

        $factory = new TodoOutputFactory(new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor()));
        $outputs = $factory->buildMany([$todo]);

        self::assertCount(1, $outputs);
        self::assertSame(
            [3, 'A faire', TodoStatusRegistry::ARCHIVED, true, '2026-11-26T10:00:00+00:00', null],
            [$outputs[0]->id, $outputs[0]->title, $outputs[0]->status, $outputs[0]->isDefault, $outputs[0]->createdAt, $outputs[0]->updatedAt],
        );
    }
}
