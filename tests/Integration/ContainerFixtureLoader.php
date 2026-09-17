<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * Doctrine's loader instantiates fixtures with `new $class()`, which cannot work here: our
 * fixtures write through the persister gateways, so they have constructor dependencies.
 *
 * Resolving them from the test container also means a DependentFixtureInterface dependency is
 * pulled in and wired for free, instead of blowing up.
 */
final class ContainerFixtureLoader extends Loader
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param class-string<FixtureInterface> $fixtureClass
     */
    public function addFixtureClass(string $fixtureClass): void
    {
        $this->addFixture($this->createFixture($fixtureClass));
    }

    protected function createFixture(string $class): FixtureInterface
    {
        $fixture = $this->container->get($class);
        assert($fixture instanceof FixtureInterface);

        return $fixture;
    }
}
