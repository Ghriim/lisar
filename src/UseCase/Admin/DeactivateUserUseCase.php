<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\UserOutputFactory;
use App\Domain\Gateway\Persister\SessionPersisterGateway;
use App\Domain\Gateway\Persister\UserPersisterGateway;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Constraint\User\SelfDeactivationConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Deactivating an account: sign-in is refused from now on, and the sessions it already has go
 * down at once — an access token stays cryptographically valid for up to 15 minutes, so leaving
 * the sessions alive would leave a deactivated person working.
 *
 * All the data is kept: an administrator can undo this.
 */
final readonly class DeactivateUserUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'deactivate_user_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private UserPersisterGateway $userPersisterGateway,
        private SessionProviderGateway $sessionProviderGateway,
        private SessionPersisterGateway $sessionPersisterGateway,
        private UserOutputFactory $outputFactory,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, int $actingUserId): UserDataOutput
    {
        $user = $this->userProviderGateway->findOneById($id);

        if (null === $user) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $violations = SelfDeactivationConstraint::validate($id, $actingUserId);
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $user->isActive = false;
        $this->userPersisterGateway->update($user);

        $liveSessions = $this->sessionProviderGateway->findAllLiveForUser($user);
        if ([] !== $liveSessions) {
            $now = DateTimeImmutable::createFromInterface($this->clock->now());
            foreach ($liveSessions as $liveSession) {
                $liveSession->revokedAt = $now;
            }

            $this->sessionPersisterGateway->updateMany($liveSessions);
        }

        return $this->outputFactory->buildOne($user);
    }
}
