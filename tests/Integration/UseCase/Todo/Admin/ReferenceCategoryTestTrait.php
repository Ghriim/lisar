<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo\Admin;

use App\UseCase\Todo\Admin\ListReferenceCategoriesUseCase;

/** Reads the common categories back, as the back-office lists them. */
trait ReferenceCategoryTestTrait
{
    /** @return list<string> */
    private function labels(): array
    {
        return array_map(static fn ($category) => $category->label, self::getContainer()->get(ListReferenceCategoriesUseCase::class)->execute($this->currentAdmin()));
    }
}
