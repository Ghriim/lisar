<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Task;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use App\Fixtures\CategoryFixtures;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Task\CreateCategoryUseCase;
use App\UseCase\Task\ListCategoriesUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateCategoryUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CreateCategoryUseCase $useCase;
    private CategoryProviderGateway $categoryProviderGateway;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CreateCategoryUseCase::class);
        $this->categoryProviderGateway = self::getContainer()->get(CategoryProviderGateway::class);

        $this->loadFixtures(CategoryFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItCreatesAPersonalCategory(): void
    {
        $output = $this->useCase->execute($this->aliceId(), new CreateCategoryDataInput('Sport'));

        self::assertSame('Sport', $output->label);
        self::assertTrue($output->isPersonal);

        // Re-read through the gateway: assert it was really persisted, and to the right account.
        $category = $this->categoryProviderGateway->findOneById($output->id);
        self::assertNotNull($category);
        self::assertSame($this->alice->id, $category->owner?->id);
    }

    public function testItAppearsInTheAccountsListAndNowhereElse(): void
    {
        $this->useCase->execute($this->aliceId(), new CreateCategoryDataInput('Sport'));

        $list = self::getContainer()->get(ListCategoriesUseCase::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        self::assertContains('Sport', array_map(
            static fn ($category) => $category->label,
            $list->execute($this->aliceId()),
        ));
        self::assertNotContains('Sport', array_map(
            static fn ($category) => $category->label,
            $list->execute($admin->id ?? 0),
        ));
    }

    public function testItRefusesALabelAReferenceCategoryAlreadyHas(): void
    {
        $this->assertRejects('Home');
    }

    public function testItRefusesALabelTheAccountAlreadyHas(): void
    {
        $this->useCase->execute($this->aliceId(), new CreateCategoryDataInput('Sport'));

        $this->assertRejects('Sport');
    }

    /**
     * Two accounts may each have a category called the same thing: the lists never meet.
     */
    public function testTwoAccountsMayUseTheSameLabel(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);

        $this->useCase->execute($this->aliceId(), new CreateCategoryDataInput('Sport'));
        $output = $this->useCase->execute($admin->id ?? 0, new CreateCategoryDataInput('Sport'));

        self::assertSame('Sport', $output->label);
    }

    public function testItRejectsABlankLabel(): void
    {
        $this->assertRejects('', 'label_required');
    }

    private function assertRejects(string $label, string $errorCode = CategoryLabelAvailableConstraint::LABEL_ALREADY_USED): void
    {
        try {
            $this->useCase->execute($this->aliceId(), new CreateCategoryDataInput($label));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains($errorCode, $exception->violations['label']);
        }
    }

    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }
}
