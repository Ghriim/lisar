<?php

declare(strict_types=1);

namespace App\UseCase\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\DataModel\User\UserIdentityDataModel;
use App\Domain\DTO\Input\User\Admin\CreateAdminDataInput;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\User\UserOutputFactory;
use App\Domain\Gateway\Persister\User\UserIdentityPersisterGateway;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\IdentityProviderRegistry;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\Validation\Validator\User\Admin\CreateAdminValidator;
use App\UseCase\UseCaseInterface;

/**
 * Creates an administrator. Reached from the console only — nothing on the HTTP surface grants
 * ROLE_ADMIN, which is what makes "the first administrator is created by a command" true rather
 * than aspirational.
 */
final readonly class CreateAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateAdminValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private UserPersisterGateway $userPersisterGateway,
        private UserIdentityPersisterGateway $userIdentityPersisterGateway,
        private PasswordHasherInterface $passwordHasher,
        private UserOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateAdminDataInput $input): UserDataOutput
    {
        $userWithSameEmail = $this->userProviderGateway->findOneByEmail($input->email);
        $userWithSameUsername = $this->userProviderGateway->findOneByUsername($input->username);

        $this->validator->validate($input, $userWithSameEmail, $userWithSameUsername);

        $user = new UserDataModel();
        $user->username = $input->username;
        $user->email = $input->email;
        $user->role = UserRoleRegistry::ADMIN;
        $user->isActive = true;

        $this->userPersisterGateway->create($user);

        $identity = new UserIdentityDataModel();
        $identity->user = $user;
        $identity->provider = IdentityProviderRegistry::PASSWORD;
        $identity->passwordHash = $this->passwordHasher->hash($input->password);

        $this->userIdentityPersisterGateway->create($identity);

        $user->identities->add($identity);

        return $this->outputFactory->buildOne($user);
    }
}
