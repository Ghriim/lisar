<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Domain\Registry\Session\SessionAudienceRegistry;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * The refresh token leaves the API as an httpOnly cookie and nothing else: no JavaScript on
 * either front end can read it, so a cross-site script cannot walk away with a 7-day session.
 *
 * One cookie per audience, each with its own name and path. Both front ends talk to the same
 * host, and a browser does not tell cookies apart by port: with a single cookie, signing in or
 * out of one front end would sign in or out of the other.
 */
final readonly class RefreshTokenCookieFactory
{
    /** Scoped to the endpoints that spend it, so it is not sent with every API call. */
    private const array COOKIES = [
        SessionAudienceRegistry::WEBSITE => ['name' => 'refresh_token', 'path' => '/api/auth'],
        SessionAudienceRegistry::ADMIN => ['name' => 'admin_refresh_token', 'path' => '/api/admin/auth'],
    ];

    public function __construct(
        #[Autowire('%refresh_token_cookie_secure%')]
        private bool $isSecure,
    ) {
    }

    public function getName(string $audience): string
    {
        return self::COOKIES[$audience]['name'];
    }

    public function buildOne(string $audience, string $refreshToken, DateTimeImmutable $expiresAt): Cookie
    {
        return $this->build($audience, $refreshToken, $expiresAt);
    }

    public function buildCleared(string $audience): Cookie
    {
        return $this->build($audience, null, 1);
    }

    private function build(string $audience, ?string $value, DateTimeImmutable|int $expires): Cookie
    {
        return Cookie::create(self::COOKIES[$audience]['name'])
            ->withValue($value)
            ->withExpires($expires)
            ->withPath(self::COOKIES[$audience]['path'])
            ->withSecure($this->isSecure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }
}
