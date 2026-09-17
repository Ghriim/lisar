<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Admin\UpdateReferenceCategoryDataInput;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Validation\Validator\Admin\UpdateReferenceCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class UpdateReferenceCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateReferenceCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateReferenceCategoryDataInput $input): CategoryDataOutput
    {
        $category = $this->categoryProviderGateway->findOneById($id);

        // A personal category is not part of this surface at all: to the back-office it does not
        // exist, which is the same answer it gives for anything belonging to an account.
        if (null === $category || true === $category->isPersonal()) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        $this->validator->validate(
            $input,
            $category,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
        );

        $category->label = $input->label;
        $this->categoryPersisterGateway->update($category);

        return $this->outputFactory->buildOne($category);
    }
}
