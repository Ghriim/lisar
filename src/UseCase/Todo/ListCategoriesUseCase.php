<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\CategoryDataOutput;
use App\Domain\Factory\OutputFactory\Todo\CategoryOutputFactory;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The categories a person may use: the reference ones, plus their own. Someone else's personal
 * category never appears here.
 */
final readonly class ListCategoriesUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<CategoryDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): array
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        return $this->outputFactory->buildMany($this->categoryProviderGateway->findAllUsableBy($owner));
    }
}
