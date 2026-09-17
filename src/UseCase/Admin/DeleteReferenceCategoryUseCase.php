<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Validation\Constraint\Task\CategoryUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeleteReferenceCategoryUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_reference_category_invalid';

    public function __construct(
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $category = $this->categoryProviderGateway->findOneById($id);

        if (null === $category || true === $category->isPersonal()) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        // Tasks across every account may sit in a common category, so this count is the only
        // thing standing between a click here and someone else's task losing its category.
        $violations = CategoryUnusedConstraint::validate(
            $this->taskProviderGateway->countForCategory($category),
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->categoryPersisterGateway->delete($category);
    }
}
