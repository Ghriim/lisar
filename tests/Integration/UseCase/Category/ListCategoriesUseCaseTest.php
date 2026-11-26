<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Category;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Category\Admin\CreateReferenceCategoryDataInput;
use App\Domain\DTO\Input\Category\CreateCategoryDataInput;
use App\Domain\Registry\Category\CategoryIconRegistry;
use App\Fixtures\Category\CategoryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Category\Admin\CreateReferenceCategoryUseCase;
use App\UseCase\Category\CreateCategoryUseCase;
use App\UseCase\Category\ListCategoriesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListCategoriesUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

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

        $categories = $this->useCase->execute($this->currentUser($alice->id ?? 0));

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
            $this->useCase->execute($this->currentUser($admin->id ?? 0)),
        ));
    }

    public function testListingTheCategoriesStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser),
        );
    }

    public function testListingTheCategoriesDoesNotGrowWithThem(): void
    {
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $createCategory = self::getContainer()->get(CreateCategoryUseCase::class);
        $createReferenceCategory = self::getContainer()->get(CreateReferenceCategoryUseCase::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser),
            function () use ($aliceId, $createCategory, $createReferenceCategory): void {
                foreach (['Sport', 'Reading', 'Garden'] as $label) {
                    $createCategory->execute($this->currentUser($aliceId), new CreateCategoryDataInput($label));
                }
                $createReferenceCategory->execute($this->currentAdmin(), new CreateReferenceCategoryDataInput('Health', CategoryIconRegistry::HOME));
            },
        );

        self::assertCount(count($fewer) + 4, $more);
    }
}
