<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Smoke test of the socle: the container compiles and the test database answers.
 *
 * It is the one test that fails loudly when a configuration change breaks the wiring, long
 * before any feature test would notice.
 */
final class KernelBootTest extends KernelTestCase
{
    public function testTheContainerCompiles(): void
    {
        self::bootKernel();

        self::assertSame('test', self::$kernel->getEnvironment());
        self::assertTrue(self::getContainer()->has(EntityManagerInterface::class));
    }

    public function testTheTestDatabaseAnswers(): void
    {
        self::bootKernel();

        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        self::assertInstanceOf(Connection::class, $connection);

        self::assertSame(1, (int) $connection->fetchOne('SELECT 1'));
    }
}
