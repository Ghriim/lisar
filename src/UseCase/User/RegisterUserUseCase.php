<?php

declare(strict_types=1);

namespace App\UseCase\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\DataModel\User\UserIdentityDataModel;
use App\Domain\DTO\Input\User\RegisterUserDataInput;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\User\UserOutputFactory;
use App\Domain\Gateway\Persister\User\UserIdentityPersisterGateway;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\IdentityProviderRegistry;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\Validation\Validator\User\RegisterUserValidator;
use App\UseCase\UseCaseInterface;

final readonly class RegisterUserUseCase implements UseCaseInterface
{
    public function __construct(
        private RegisterUserValidator $validator,
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
    public function execute(RegisterUserDataInput $input): UserDataOutput
    {
        $userWithSameEmail = $this->userProviderGateway->findOneByEmail($input->email);
        $userWithSameUsername = $this->userProviderGateway->findOneByUsername($input->username);

        $this->validator->validate($input, $userWithSameEmail, $userWithSameUsername);

        $user = new UserDataModel();
        $user->username = $input->username;
        $user->email = $input->email;
        $user->role = UserRoleRegistry::USER;
        $user->isActive = true;

        $this->userPersisterGateway->create($user);

        // The identity needs the account to exist, so it is written second. The plain password
        // never leaves this line.
        $identity = new UserIdentityDataModel();
        $identity->user = $user;
        $identity->provider = IdentityProviderRegistry::PASSWORD;
        $identity->passwordHash = $this->passwordHasher->hash($input->password);

        $this->userIdentityPersisterGateway->create($identity);

        $user->identities->add($identity);

        return $this->outputFactory->buildOne($user);
    }
}
