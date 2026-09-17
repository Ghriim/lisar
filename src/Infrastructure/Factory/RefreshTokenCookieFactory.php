<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * The refresh token leaves the API as an httpOnly cookie and nothing else: no JavaScript on
 * either front end can read it, so a cross-site script cannot walk away with a 7-day session.
 */
final readonly class RefreshTokenCookieFactory
{
    public const string COOKIE_NAME = 'refresh_token';

    /** Scoped to the endpoints that spend it, so it is not sent with every API call. */
    private const string COOKIE_PATH = '/api/auth';

    public function __construct(
        #[Autowire('%refresh_token_cookie_secure%')]
        private bool $isSecure,
    ) {
    }

    public function buildOne(string $refreshToken, DateTimeImmutable $expiresAt): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue($refreshToken)
            ->withExpires($expiresAt)
            ->withPath(self::COOKIE_PATH)
            ->withSecure($this->isSecure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function buildCleared(): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue(null)
            ->withExpires(1)
            ->withPath(self::COOKIE_PATH)
            ->withSecure($this->isSecure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }
}
