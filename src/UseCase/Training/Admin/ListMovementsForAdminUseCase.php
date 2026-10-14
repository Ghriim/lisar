<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListMovementsForAdminDataInput;
use App\Domain\DTO\Output\Training\MovementDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\MovementOutputFactory;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Validator\Training\ListMovementsForAdminValidator;
use App\UseCase\UseCaseInterface;

/**
 * Every common movement for the back-office, active and retired alike, by name. What people will
 * create for themselves never shows here. Not paginated: reference data in the tens of rows.
 */
final readonly class ListMovementsForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private ListMovementsForAdminValidator $validator,
        private MovementProviderGateway $movementProviderGateway,
        private MovementOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<MovementDataOutput>
     *
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListMovementsForAdminDataInput $input = new ListMovementsForAdminDataInput()): array
    {
        $this->validator->validate($currentUser, $input);

        return $this->outputFactory->buildMany($this->movementProviderGateway->findAllCommonForAdminList(
            $input->isActive,
            $input->movementFamilyId,
            $input->muscleGroupId,
            $input->muscleId,
            $input->equipmentId,
        ));
    }
}
