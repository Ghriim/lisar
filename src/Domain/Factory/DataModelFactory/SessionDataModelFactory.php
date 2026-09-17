<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Session\RefreshTokenGenerator;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function sprintf;

final readonly class SessionDataModelFactory
{
    public function __construct(
        private RefreshTokenGenerator $refreshTokenGenerator,
        #[Autowire('%refresh_token_ttl%')]
        private int $refreshTokenTtlInSeconds,
    ) {
    }

    /**
     * Builds the row that holds a session. It stores the hash; the caller keeps the raw token,
     * which is never written down anywhere.
     */
    public function buildOne(UserDataModel $user, string $refreshToken, DateTimeImmutable $now): SessionDataModel
    {
        $session = new SessionDataModel();
        $session->user = $user;
        $session->refreshTokenHash = $this->refreshTokenGenerator->hash($refreshToken);
        $session->expiresAt = $now->add(new DateInterval(sprintf('PT%dS', $this->refreshTokenTtlInSeconds)));

        return $session;
    }
}
