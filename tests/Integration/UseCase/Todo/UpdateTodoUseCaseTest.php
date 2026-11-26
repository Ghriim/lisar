<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Todo\TodoUpdatedEvent;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Constraint\Todo\TodoTitleAvailableConstraint;
use App\Fixtures\Todo\TodoFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\UpdateTodoUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateTodoUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use RecordedEventsTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private UpdateTodoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(UpdateTodoUseCase::class);
        $this->loadFixtures(TodoFixtures::class);
    }

    public function testItRenamesTheTodoAndSaysItDoesNotArchiveIt(): void
    {
        $this->forgetRaisedEvents();
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_HOME), new SaveTodoDataInput(' Appartement '));

        self::assertSame('Appartement', $output->title);
        self::assertSame('Appartement', self::getContainer()->get(TodoProviderGateway::class)->findOneById($output->id)?->title);
        self::assertFalse($this->assertEventRaised(TodoUpdatedEvent::class)->archives);
    }

    /** The default is never archived nor deleted, but it may be renamed. */
    public function testTheDefaultMayBeRenamed(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_DEFAULT), new SaveTodoDataInput('Mes tâches'));

        self::assertSame(['Mes tâches', true], [$output->title, $output->isDefault]);
    }

    public function testATodoMayKeepItsOwnTitleWhateverTheCase(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_HOME), new SaveTodoDataInput('MAISON'));

        self::assertSame('MAISON', $output->title);
    }

    public function testItRefusesATitleAnotherTodoHas(): void
    {
        $this->assertRefused(['title' => [TodoTitleAvailableConstraint::TITLE_ALREADY_USED]], $this->todoId(TodoFixtures::ALICE_HOME), 'a faire', $this->aliceId());
    }

    public function testItRefusesToRenameAnArchivedTodo(): void
    {
        $this->assertRefused(['id' => [TodoActiveConstraint::TODO_ARCHIVED]], $this->todoId(TodoFixtures::ALICE_ARCHIVED), 'Projets', $this->aliceId());
    }

    public function testItRefusesSomeoneElsesTodo(): void
    {
        $this->assertRefused(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $this->todoId(TodoFixtures::ALICE_HOME), 'À moi', $this->adminId());
    }

    public function testATodoThatDoesNotExistIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($this->currentUser($this->aliceId()), 999999, new SaveTodoDataInput('Rien'));
    }

    public function testRenamingStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $todoId = $this->todoId(TodoFixtures::ALICE_HOME);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $todoId, new SaveTodoDataInput('Appartement')),
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, int $todoId, string $title, int $callerId): void
    {
        try {
            $this->useCase->execute($this->currentUser($callerId), $todoId, new SaveTodoDataInput($title));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame($violations, $exception->violations);
        }
    }
}
