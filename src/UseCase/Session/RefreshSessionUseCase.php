<?php

declare(strict_types=1);

namespace App\UseCase\Session;

use App\Domain\DTO\Output\Session\SessionDataOutput;
use App\Domain\Exception\AccountDeactivatedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Factory\DataModelFactory\SessionDataModelFactory;
use App\Domain\Factory\OutputFactory\SessionOutputFactory;
use App\Domain\Gateway\Persister\SessionPersisterGateway;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Session\AccessTokenIssuerInterface;
use App\Domain\Session\RefreshTokenGenerator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Renewing a session silently: the presented refresh token is spent and replaced.
 */
final readonly class RefreshSessionUseCase implements UseCaseInterface
{
    public function __construct(
        private SessionProviderGateway $sessionProviderGateway,
        private SessionPersisterGateway $sessionPersisterGateway,
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private SessionDataModelFactory $sessionDataModelFactory,
        private SessionOutputFactory $outputFactory,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws InvalidCredentialsException
     * @throws AccountDeactivatedException
     */
    public function execute(string $refreshToken): SessionDataOutput
    {
        $session = $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($refreshToken),
        );

        if (null === $session) {
            throw new InvalidCredentialsException();
        }

        $now = DateTimeImmutable::createFromInterface($this->clock->now());

        if (null !== $session->revokedAt) {
            // A token that was already spent is being replayed. We cannot tell the legitimate
            // holder from whoever stole it, so every live session of that account goes down.
            $liveSessions = $this->sessionProviderGateway->findAllLiveForUser($session->user);
            foreach ($liveSessions as $liveSession) {
                $liveSession->revokedAt = $now;
            }
            $this->sessionPersisterGateway->updateMany($liveSessions);

            throw new InvalidCredentialsException();
        }

        if ($session->expiresAt <= $now) {
            throw new InvalidCredentialsException();
        }

        if (false === $session->user->isActive) {
            throw new AccountDeactivatedException();
        }

        // Rotation: the old row is spent, a new one takes over. The lifetime is not extended
        // beyond the new token's own, which is what makes a stolen token expire for good.
        $session->revokedAt = $now;
        $this->sessionPersisterGateway->update($session);

        $newRefreshToken = $this->refreshTokenGenerator->generate();
        $newSession = $this->sessionDataModelFactory->buildOne($session->user, $newRefreshToken, $now);
        $this->sessionPersisterGateway->create($newSession);

        return $this->outputFactory->buildOne(
            $newSession,
            $this->accessTokenIssuer->issue($session->user),
            $newRefreshToken,
        );
    }
}
