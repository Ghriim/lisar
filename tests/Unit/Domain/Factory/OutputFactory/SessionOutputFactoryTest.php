<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\Factory\OutputFactory\SessionOutputFactory;
use App\Domain\Session\AccessTokenIssuerInterface;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

use const JSON_THROW_ON_ERROR;

#[AllowMockObjectsWithoutExpectations]
final class SessionOutputFactoryTest extends TestCase
{
    private SessionOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $accessTokenIssuer = $this->createMock(AccessTokenIssuerInterface::class);
        $accessTokenIssuer->method('getTtlInSeconds')->willReturn(900);

        $this->factory = new SessionOutputFactory($accessTokenIssuer);
    }

    public function testItBuildsTheSessionOutput(): void
    {
        $session = new SessionDataModel();
        $session->expiresAt = new DateTimeImmutable('2026-09-24T10:00:00+00:00');

        $output = $this->factory->buildOne($session, 'an-access-token', 'a-refresh-token');

        self::assertSame('an-access-token', $output->accessToken);
        self::assertSame(900, $output->expiresIn);
        self::assertSame('Bearer', $output->tokenType);
        self::assertSame('a-refresh-token', $output->refreshToken);
        self::assertSame($session->expiresAt, $output->refreshTokenExpiresAt);
    }

    /**
     * The refresh token leaves as a cookie only: it must never appear in the JSON body.
     */
    public function testTheRefreshTokenStaysOutOfTheSerialisedBody(): void
    {
        $session = new SessionDataModel();
        $session->expiresAt = new DateTimeImmutable('2026-09-24T10:00:00+00:00');

        $body = json_encode($this->factory->buildOne($session, 'an-access-token', 'a-refresh-token'), JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('a-refresh-token', $body);
        self::assertStringNotContainsString('refreshToken', $body);
        self::assertSame(
            ['accessToken' => 'an-access-token', 'expiresIn' => 900, 'tokenType' => 'Bearer'],
            json_decode($body, true, 512, JSON_THROW_ON_ERROR),
        );
    }
}
