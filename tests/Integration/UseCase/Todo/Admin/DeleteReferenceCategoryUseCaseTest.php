<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryUnusedConstraint;
use App\Domain\Validation\Validator\Todo\Admin\DeleteReferenceCategoryValidator;
use App\Fixtures\Todo\CategoryFixtures;
use App\Fixtures\Todo\TaskFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\Admin\DeleteReferenceCategoryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteReferenceCategoryUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use ReferenceCategoryTestTrait;
    use SignedInUserTrait;

    private DeleteReferenceCategoryUseCase $delete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->delete = self::getContainer()->get(DeleteReferenceCategoryUseCase::class);
    }

    public function testItDeletesAnEmptyCommonCategory(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $work = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class);
        $this->delete->execute($this->currentAdmin(), $work->id ?? 0);

        self::assertSame(['Home'], $this->labels());
    }

    /**
     * Tasks from any account may sit in a common category: this count is what stands between a
     * click here and someone else's task losing its category.
     */
    public function testItRefusesToDeleteACategoryHoldingSomeonesTasks(): void
    {
        $this->loadFixtures(TaskFixtures::class);

        $home = $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class);

        try {
            $this->delete->execute($this->currentAdmin(), $home->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(CategoryUnusedConstraint::CATEGORY_IN_USE, $exception->violations['id']);
        }
    }

    public function testItFailsOnAnUnknownCategory(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->delete->execute($this->currentAdmin(), 123456789);
    }

    public function testDeletingACommonCategoryStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(CategoryFixtures::class);
        $workId = $this->getReference(CategoryFixtures::WORK, CategoryDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentAdmin) => $this->delete->execute($currentAdmin, $workId),
        );
    }
}
