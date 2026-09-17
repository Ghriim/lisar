<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\UpdateCategoryDataInput;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\CategoryOutputFactory;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Validator\Task\UpdateCategoryValidator;
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
        private UserProviderGateway $userProviderGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private CategoryOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $categoryId, UpdateCategoryDataInput $input): CategoryDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $category = $this->categoryProviderGateway->findOneById($categoryId);

        // Someone else's personal category is not visible to this account at all.
        if (null === $category || false === $category->isUsableBy($owner)) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        $this->validator->validate(
            $input,
            $category,
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, null),
            $this->categoryProviderGateway->findOneByLabelForOwner($input->label, $owner),
        );

        $category->label = $input->label;
        $this->categoryPersisterGateway->update($category);

        return $this->outputFactory->buildOne($category);
    }
}
