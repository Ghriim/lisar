<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Constraint\Task\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Task\CategoryUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeleteCategoryUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_category_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $categoryId): void
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $category = $this->categoryProviderGateway->findOneById($categoryId);

        if (null === $category || false === $category->isUsableBy($owner)) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        $violations = CategoryEditableConstraint::validate($category);
        $violations = CategoryUnusedConstraint::validate(
            $this->taskProviderGateway->countForCategory($category),
            $violations,
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->categoryPersisterGateway->delete($category);
    }
}
