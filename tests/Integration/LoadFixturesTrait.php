<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

use function assert;

trait LoadFixturesTrait
{
    private ?ReferenceRepository $referenceRepository = null;

    /**
     * Runs exactly the fixture classes a test names, in dependency order.
     *
     * @param class-string<FixtureInterface> ...$fixtureClasses
     */
    private function loadFixtures(string ...$fixtureClasses): void
    {
        $container = self::getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');
        assert($entityManager instanceof EntityManagerInterface);

        $loader = new ContainerFixtureLoader($container);
        foreach ($fixtureClasses as $fixtureClass) {
            $loader->addFixtureClass($fixtureClass);
        }

        // DELETE, never TRUNCATE: MySQL implicitly commits around TRUNCATE, which would break out
        // of the transaction dama/doctrine-test-bundle wraps this test method in.
        $purger = new ORMPurger($entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);

        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($loader->getFixtures());

        $this->referenceRepository = $executor->getReferenceRepository();

        $entityManager->clear();
    }

    /**
     * Reaches a seeded row through the reference its fixture registered.
     *
     * The purge runs in DELETE mode, which leaves AUTO_INCREMENT counters untouched, so ids
     * differ from one test method to the next: this is the only way to find a row again.
     *
     * @template T of DataModelInterface
     *
     * @param class-string<T> $dataModelClass
     *
     * @return T
     */
    private function getReference(string $name, string $dataModelClass): DataModelInterface
    {
        if (null === $this->referenceRepository) {
            throw new LogicException('Call loadFixtures() before reaching for a reference.');
        }

        return $this->referenceRepository->getReference($name, $dataModelClass);
    }
}
