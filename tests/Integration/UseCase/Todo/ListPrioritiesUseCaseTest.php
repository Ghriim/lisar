<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Fixtures\Todo\PriorityFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\ListPrioritiesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListPrioritiesUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

    private ListPrioritiesUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListPrioritiesUseCase::class);

        $this->loadFixtures(PriorityFixtures::class);
    }

    public function testItReturnsEveryPriorityLightestWeightFirst(): void
    {
        $priorities = $this->useCase->execute($this->anyCurrentUser());

        self::assertSame(['High', 'Normal', 'Low'], array_map(
            static fn ($priority) => $priority->label,
            $priorities,
        ));
        self::assertSame([10, 20, 30], array_map(
            static fn ($priority) => $priority->weight,
            $priorities,
        ));
    }

    public function testExactlyOnePriorityIsTheDefault(): void
    {
        $defaults = array_filter($this->useCase->execute($this->anyCurrentUser()), static fn ($priority) => $priority->isDefault);

        self::assertCount(1, $defaults);
    }
}
