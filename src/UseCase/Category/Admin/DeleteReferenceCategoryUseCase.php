<?php

declare(strict_types=1);

namespace App\UseCase\Category\Admin;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Category\CategoryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Category\Admin\DeleteReferenceCategoryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeleteReferenceCategoryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteReferenceCategoryValidator $validator,
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
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $category = $this->categoryProviderGateway->findOneById($id);

        if (null === $category || true === $category->isPersonal()) {
            throw new DataModelNotFoundException(CategoryDataModel::class);
        }

        // Tasks across every account and catalogue habits may sit in a common category, so this
        // count is the only thing standing between a click here and someone losing their category.
        $this->validator->validate($currentUser, $this->countUses($category));

        $this->categoryPersisterGateway->delete($category);
    }

    /** Every domain that can sit in a category, counted together: one is enough to refuse. */
    private function countUses(CategoryDataModel $category): int
    {
        return $this->taskProviderGateway->countForCategory($category)
            + $this->habitProviderGateway->countForCategory($category);
    }
}
