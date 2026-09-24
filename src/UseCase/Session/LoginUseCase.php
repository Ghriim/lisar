<?php

declare(strict_types=1);

namespace App\UseCase\Session;

use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\DTO\Output\Session\SessionDataOutput;
use App\Domain\Exception\AccountDeactivatedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\ValidationException;
use App\Domain\Exception\WrongAudienceException;
use App\Domain\Factory\DataModelFactory\SessionDataModelFactory;
use App\Domain\Factory\OutputFactory\SessionOutputFactory;
use App\Domain\Gateway\Persister\SessionPersisterGateway;
use App\Domain\Gateway\Persister\UserPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Session\SessionAudienceRegistry;
use App\Domain\Registry\User\IdentityProviderRegistry;
use App\Domain\Session\AccessTokenIssuerInterface;
use App\Domain\Session\RefreshTokenGenerator;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\Validation\Validator\Session\LoginValidator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Signing in: credentials in, a session out — for one audience, the front end asking.
 */
final readonly class LoginUseCase implements UseCaseInterface
{
    public function __construct(
        private LoginValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private UserPersisterGateway $userPersisterGateway,
        private SessionPersisterGateway $sessionPersisterGateway,
        private PasswordHasherInterface $passwordHasher,
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private SessionDataModelFactory $sessionDataModelFactory,
        private SessionOutputFactory $outputFactory,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ValidationException
     * @throws InvalidCredentialsException
     * @throws AccountDeactivatedException
     * @throws WrongAudienceException
     */
    public function execute(LoginDataInput $input, string $audience): SessionDataOutput
    {
        $this->validator->validate($input);

        $user = $this->userProviderGateway->findOneByEmail($input->email);
        $identity = $user?->getIdentityForProvider(IdentityProviderRegistry::PASSWORD);

        if (null === $user || null === $identity || null === $identity->passwordHash) {
            throw new InvalidCredentialsException();
        }

        if (false === $this->passwordHasher->verify($identity->passwordHash, $input->password)) {
            throw new InvalidCredentialsException();
        }

        // Checked after the password, so that a stranger cannot learn which accounts exist by
        // reading the difference between 401 and 403.
        if (false === $user->isActive) {
            throw new AccountDeactivatedException();
        }

        // Same reasoning: only once the password checked out does the answer say which door the
        // account belongs to.
        if (SessionAudienceRegistry::REQUIRED_ROLE[$audience] !== $user->role) {
            throw new WrongAudienceException();
        }

        $now = DateTimeImmutable::createFromInterface($this->clock->now());

        $user->lastSignedInAt = $now;
        $this->userPersisterGateway->update($user);

        $refreshToken = $this->refreshTokenGenerator->generate();
        $session = $this->sessionDataModelFactory->buildOne($user, $refreshToken, $now);
        $this->sessionPersisterGateway->create($session);

        return $this->outputFactory->buildOne($session, $this->accessTokenIssuer->issue($user), $refreshToken);
    }
}
