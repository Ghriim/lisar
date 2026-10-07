<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\Gateway\Persister\Todo\CategoryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
