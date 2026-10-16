<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\Admin\CreateReferenceCategoryDataInput;
use App\Fixtures\Todo\CategoryFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\Admin\CreateReferenceCategoryUseCase;
use App\UseCase\Todo\Admin\ListReferenceCategoriesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListReferenceCategoriesUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use ReferenceCategoryTestTrait;
    use SignedInUserTrait;

    private ListReferenceCategoriesUseCase $list;
    private CreateReferenceCategoryUseCase $create;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListReferenceCategoriesUseCase::class);
        $this->create = self::getContainer()->get(CreateReferenceCategoryUseCase::class);
    }

    /**
     * What a person creates for themselves is theirs: the back-office never sees it.
     */
    public function testItListsTheCommonCategoriesAndNoPersonalOne(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        self::assertSame(['Home', 'Work'], $this->labels());
    }

    public function testListingTheCommonCategoriesStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentAdmin) => $this->list->execute($currentAdmin),
        );
    }

    public function testListingTheCommonCategoriesDoesNotGrowWithThem(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentAdmin) => $this->list->execute($currentAdmin),
            function (): void {
                foreach (['Health', 'Money', 'Family'] as $label) {
                    $this->create->execute($this->currentAdmin(), new CreateReferenceCategoryDataInput($label));
                }
            },
        );

        self::assertCount(count($fewer) + 3, $more);
    }
}
