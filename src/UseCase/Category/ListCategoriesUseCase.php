<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Category\CategoryDataOutput;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Validation\Validator\Category\ListCategoriesValidator;
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
