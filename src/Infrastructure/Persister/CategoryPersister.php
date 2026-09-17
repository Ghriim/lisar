<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\Gateway\Persister\CategoryPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<CategoryDataModel>
 */
final class CategoryPersister extends AbstractBaseMysqlPersister implements CategoryPersisterGateway
{
    public function create(CategoryDataModel $category): CategoryDataModel
    {
        return $this->persistAndStampCreate($category);
    }

    public function update(CategoryDataModel $category): CategoryDataModel
    {
        return $this->persistAndStampUpdate($category);
    }

    public function delete(CategoryDataModel $category): void
    {
        $this->persistDelete($category);
    }
}
