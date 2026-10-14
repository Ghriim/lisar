<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\CategoryDataOutput;
use App\Domain\Factory\OutputFactory\Todo\CategoryOutputFactory;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Validation\Validator\Todo\ListCategoriesValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The categories a person may use: the reference ones, plus their own. Someone else's personal
 * category never appears here.
 */
final readonly class ListCategoriesUseCase implements UseCaseInterface
{
    public function __construct(
        private ListCategoriesValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<CategoryDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildMany($this->categoryProviderGateway->findAllUsableBy($currentUser));
    }
}
