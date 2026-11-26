<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Category\UpdateCategoryDataInput;
use App\Domain\DTO\Output\Category\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use App\Domain\Gateway\Persister\Category\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Validation\Validator\Category\UpdateCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Renaming one's own category. A reference category comes back as not editable — the person can
 * see it, so pretending it does not exist would only puzzle them.
 */
final readonly class UpdateCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $categoryId, UpdateCategoryDataInput $input): CategoryDataOutput
    {
        $category = $this->categoryProviderGateway->findOneById($categoryId);

        // Someone else's personal category is not visible to this account at all.
        if (null === $category || false === $category->isUsableBy($currentUser)) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        $this->validator->validate(
            $currentUser,
            $input,
            $category,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, $currentUser),
        );

        $category->label = $input->label;
        $this->categoryPersisterGateway->update($category);

        return $this->outputFactory->buildOne($category);
    }
}
