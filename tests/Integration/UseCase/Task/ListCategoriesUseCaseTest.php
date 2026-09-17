<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\ListCategoriesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListCategoriesUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListCategoriesUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListCategoriesUseCase::class);

        $this->loadFixtures(CategoryFixtures::class);
    }

    public function testItReturnsTheReferenceCategoriesAndTheAccountsOwn(): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        $categories = $this->useCase->execute($alice->id ?? 0);

        self::assertSame(['Home', 'Side project', 'Work'], array_map(
            static fn ($category) => $category->label,
            $categories,
        ));

        $personal = array_values(array_filter($categories, static fn ($category) => $category->isPersonal));
        self::assertCount(1, $personal);
        self::assertSame('Side project', $personal[0]->label);
    }

    public function testItHidesSomeoneElsesPersonalCategory(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        self::assertSame(['Home', 'Work'], array_map(
            static fn ($category) => $category->label,
            $this->useCase->execute($admin->id ?? 0),
        ));
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute(123456789);
    }
}
