<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\DataModelFactory\SessionDataModelFactory;
use App\Domain\Session\RefreshTokenGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

use const DATE_ATOM;

final class SessionDataModelFactoryTest extends TestCase
{
    private const int TTL_IN_SECONDS = 604800;

    private RefreshTokenGenerator $generator;
    private SessionDataModelFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RefreshTokenGenerator();
        $this->factory = new SessionDataModelFactory($this->generator, self::TTL_IN_SECONDS);
    }

    public function testItStoresTheHashAndNeverTheToken(): void
    {
        $session = $this->factory->buildOne(new UserDataModel(), 'a-refresh-token', new DateTimeImmutable());

        self::assertSame($this->generator->hash('a-refresh-token'), $session->refreshTokenHash);
        self::assertStringNotContainsString('a-refresh-token', $session->refreshTokenHash);
    }

    public function testItExpiresTheSessionAfterTheConfiguredLifetime(): void
    {
        $now = new DateTimeImmutable('2026-09-17T10:00:00+00:00');

        $session = $this->factory->buildOne(new UserDataModel(), 'a-refresh-token', $now);

        self::assertSame('2026-09-24T10:00:00+00:00', $session->expiresAt->format(DATE_ATOM));
        self::assertTrue($session->isUsable($now));
        self::assertFalse($session->isUsable($session->expiresAt));
    }
}
