<?php

declare(strict_types=1);

namespace App\UseCase\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\User\UserOutputFactory;
use App\Domain\Gateway\Persister\User\SessionPersisterGateway;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Domain\Gateway\Provider\User\SessionProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\User\Admin\DeactivateUserValidator;
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
    public function __construct(
        private DeactivateUserValidator $validator,
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
    public function execute(UserDataModel $currentUser, int $id): UserDataOutput
    {
        $user = $this->userProviderGateway->findOneById($id);

        if (null === $user) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate($currentUser, $id);

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
