<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\Input\Admin\ListSetTypesForAdminDataInput;
use App\Domain\DTO\Output\Workout\SetTypeDataOutput;
use App\Domain\Factory\OutputFactory\SetTypeOutputFactory;
use App\Domain\Gateway\Provider\SetTypeProviderGateway;
use App\UseCase\UseCaseInterface;

/** Every set type for the back-office, active and retired alike, by name. Not paginated: reference data an administrator keeps in a handful of rows. */
final readonly class ListSetTypesForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<SetTypeDataOutput>
     */
    public function execute(ListSetTypesForAdminDataInput $input = new ListSetTypesForAdminDataInput()): array
    {
        return $this->outputFactory->buildMany($this->setTypeProviderGateway->findAllForAdminList($input->isActive));
    }
}
