<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\Admin\CreateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryLabelAvailableConstraint;
use App\Fixtures\Todo\CategoryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\Admin\CreateReferenceCategoryUseCase;
use App\UseCase\Todo\ListCategoriesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateReferenceCategoryUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateReferenceCategoryUseCase $create;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateReferenceCategoryUseCase::class);
    }

    public function testItCreatesACategoryEveryoneSees(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $output = $this->create->execute($this->currentAdmin(), new CreateReferenceCategoryDataInput('Santé'));

        self::assertSame('Santé', $output->label);
        self::assertFalse($output->isPersonal);

        // It shows up in what an account may use, without that account doing anything.
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertContains('Santé', array_map(
            static fn ($category) => $category->label,
            self::getContainer()->get(ListCategoriesUseCase::class)->execute($this->currentUser($alice->id ?? 0)),
        ));
    }

    public function testItRefusesALabelAnotherCommonCategoryHas(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        try {
            $this->create->execute($this->currentAdmin(), new CreateReferenceCategoryDataInput('Home'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    /**
     * A personal label is private, so it does not stand in the back-office's way. The website
     * shows the two sets apart, which is what keeps the duplicate readable.
     */
    public function testAPersonalLabelDoesNotBlockACommonOne(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        self::assertSame('Side project', $this->create->execute(
            $this->currentAdmin(),
            new CreateReferenceCategoryDataInput('Side project'),
        )->label);
    }

    public function testCreatingACommonCategoryStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(CategoryFixtures::class);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentAdmin) => $this->create->execute($currentAdmin, new CreateReferenceCategoryDataInput('Santé')),
        );
    }
}
