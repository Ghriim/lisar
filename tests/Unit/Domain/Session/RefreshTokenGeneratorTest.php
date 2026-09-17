<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Session;

use App\Domain\Session\RefreshTokenGenerator;
use PHPUnit\Framework\TestCase;

use function strlen;

final class RefreshTokenGeneratorTest extends TestCase
{
    private RefreshTokenGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RefreshTokenGenerator();
    }

    public function testItGeneratesAnOpaqueTokenOf64HexCharacters(): void
    {
        $token = $this->generator->generate();

        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testItNeverGeneratesTheSameTokenTwice(): void
    {
        self::assertNotSame($this->generator->generate(), $this->generator->generate());
    }

    public function testItHashesDeterministically(): void
    {
        $token = $this->generator->generate();

        self::assertSame($this->generator->hash($token), $this->generator->hash($token));
    }

    public function testTheHashHidesTheToken(): void
    {
        $token = $this->generator->generate();
        $hash = $this->generator->hash($token);

        self::assertNotSame($token, $hash);
        // The column is 64 characters wide because the hash always is.
        self::assertSame(64, strlen($hash));
    }
}
