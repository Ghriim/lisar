<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\User;

use App\Domain\DTO\DataModel\User\SessionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\User\RefreshTokenGenerator;
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
