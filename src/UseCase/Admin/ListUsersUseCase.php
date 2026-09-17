<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\Input\Admin\ListUsersDataInput;
use App\Domain\DTO\Output\PaginatedListDataOutput;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\UserOutputFactory;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Validator\Admin\ListUsersValidator;
use App\UseCase\UseCaseInterface;

final readonly class ListUsersUseCase implements UseCaseInterface
{
    public function __construct(
        private ListUsersValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private UserOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return PaginatedListDataOutput<UserDataOutput>
     *
     * @throws ValidationException
     */
    public function execute(ListUsersDataInput $input): PaginatedListDataOutput
    {
        $this->validator->validate($input);

        $users = $this->userProviderGateway->findAllForAdminList(
            $input->search,
            $input->isActive,
            $input->perPage,
            $input->getOffset(),
        );

        return $this->outputFactory->buildPaginated(
            $users,
            $this->userProviderGateway->countAllForAdminList($input->search, $input->isActive),
            $input->page,
            $input->perPage,
        );
    }
}
