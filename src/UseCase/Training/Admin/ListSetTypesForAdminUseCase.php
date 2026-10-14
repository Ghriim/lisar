<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListSetTypesForAdminDataInput;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Validator\Training\ListSetTypesForAdminValidator;
use App\UseCase\UseCaseInterface;

/** Every set type for the back-office, active and retired alike, by name. Not paginated: reference data an administrator keeps in a handful of rows. */
final readonly class ListSetTypesForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private ListSetTypesForAdminValidator $validator,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<SetTypeDataOutput>
     *
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListSetTypesForAdminDataInput $input = new ListSetTypesForAdminDataInput()): array
    {
        $this->validator->validate($currentUser, $input);

        return $this->outputFactory->buildMany($this->setTypeProviderGateway->findAllForAdminList($input->isActive));
    }
}
