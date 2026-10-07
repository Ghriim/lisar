<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Todo\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Constraint\Todo\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUnusedConstraint;
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
