<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Category\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Category\DeleteCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeleteCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteCategoryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private CategoryPersisterGateway $categoryPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
        private HabitProviderGateway $habitProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $categoryId): void
    {
        $category = $this->categoryProviderGateway->findOneById($categoryId);

        if (null === $category || false === $category->isUsableBy($currentUser)) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        $this->validator->validate($currentUser, $category, $this->countUses($category));

        $this->categoryPersisterGateway->delete($category);
    }

    /** Every domain that can sit in a category, counted together: one is enough to refuse. */
    private function countUses(CategoryDataModel $category): int
    {
        return $this->taskProviderGateway->countForCategory($category)
            + $this->habitProviderGateway->countForCategory($category);
    }
}
