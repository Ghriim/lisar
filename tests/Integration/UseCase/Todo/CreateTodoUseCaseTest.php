<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Todo\TodoTitleAvailableConstraint;
use App\Domain\Validation\Validator\Todo\CreateTodoValidator;
use App\Fixtures\Todo\TodoFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\CreateTodoUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateTodoUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private CreateTodoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CreateTodoUseCase::class);
        $this->loadFixtures(TodoFixtures::class);
    }

    /** Active, never the default, its title stored trimmed. */
    public function testItCreatesAnActiveTodoWithItsTitleTrimmed(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), new SaveTodoDataInput('  Travail  '));

        self::assertSame(['Travail', TodoStatusRegistry::ACTIVE, false], [$output->title, $output->status, $output->isDefault]);

        $reread = self::getContainer()->get(TodoProviderGateway::class)->findOneById($output->id);
        self::assertNotNull($reread);
        self::assertSame(['Travail', $this->aliceId()], [$reread->title, $reread->owner->id]);
    }

    /** Case and surrounding spaces aside, « maison » is « Maison ». */
    public function testItRefusesATitleTheAccountAlreadyHasWhateverTheCase(): void
    {
        foreach (['Maison', ' maison ', 'MAISON'] as $title) {
            try {
                $this->useCase->execute($this->currentUser($this->aliceId()), new SaveTodoDataInput($title));
                self::fail('Expected ValidationException');
            } catch (ValidationException $exception) {
                self::assertSame(CreateTodoValidator::ERROR_CODE, $exception->errorCode);
                self::assertSame(['title' => [TodoTitleAvailableConstraint::TITLE_ALREADY_USED]], $exception->violations);
            }
        }
    }

    /** Unique per account: another account may have its own « Maison ». */
    public function testAnotherAccountMayUseTheSameTitle(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->adminId()), new SaveTodoDataInput('Maison'));

        self::assertSame('Maison', $output->title);
    }

    public function testItRejectsABlankTitle(): void
    {
        try {
            $this->useCase->execute($this->currentUser($this->aliceId()), new SaveTodoDataInput('   '));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['title' => ['title_required']], $exception->violations);
        }
    }

    public function testCreatingStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, new SaveTodoDataInput('Travail')),
        );
    }
}
