<?php

declare(strict_types=1);

namespace App\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\CategoryDataOutput;
use App\Domain\Factory\OutputFactory\Todo\CategoryOutputFactory;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Validation\Validator\Todo\Admin\ListReferenceCategoriesValidator;
use App\UseCase\UseCaseInterface;

/**
 * The common categories, and only those: what people create for themselves is theirs, and the
 * back-office never sees it.
 */
final readonly class ListReferenceCategoriesUseCase implements UseCaseInterface
{
    public function __construct(
        private ListReferenceCategoriesValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<CategoryDataOutput>
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildMany($this->categoryProviderGateway->findAllReference());
    }
}
