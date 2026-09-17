<?php

declare(strict_types=1);

namespace App\UseCase\Session;

use App\Domain\Gateway\Persister\SessionPersisterGateway;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Session\RefreshTokenGenerator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Signing out, everywhere: every live session of the account goes down, not just the one the
 * request came with. Signing out is what someone does when they suspect they should, so it errs
 * on the side of dropping too much.
 *
 * Idempotent on purpose: an unknown token is not an error, the caller wanted the sessions gone
 * and they are gone.
 */
final readonly class DeleteSessionUseCase implements UseCaseInterface
{
    public function __construct(
        private SessionProviderGateway $sessionProviderGateway,
        private SessionPersisterGateway $sessionPersisterGateway,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private ClockInterface $clock,
    ) {
    }

    public function execute(string $refreshToken): void
    {
        // A token that was already spent still identifies whose sessions to drop: after a
        // rotation raced with a sign-out, the caller holds the previous one and still means it.
        $session = $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($refreshToken),
        );

        if (null === $session) {
            return;
        }

        $liveSessions = $this->sessionProviderGateway->findAllLiveForUser($session->user);

        if ([] === $liveSessions) {
            return;
        }

        $now = DateTimeImmutable::createFromInterface($this->clock->now());
        foreach ($liveSessions as $liveSession) {
            $liveSession->revokedAt = $now;
        }

        $this->sessionPersisterGateway->updateMany($liveSessions);
    }
}
