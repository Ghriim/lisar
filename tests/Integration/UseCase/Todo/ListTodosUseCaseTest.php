<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTodosDataInput;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Validator\Todo\ListTodosValidator;
use App\Fixtures\Todo\TodoFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\CreateTodoUseCase;
use App\UseCase\Todo\ListTodosUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListTodosUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private ListTodosUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ListTodosUseCase::class);
        $this->loadFixtures(TodoFixtures::class);
    }

    /** « A faire » first, then by title; someone else's never. */
    public function testItListsEveryTodoOfTheAccountTheDefaultFirst(): void
    {
        $todos = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTodosDataInput());

        self::assertSame(['A faire', 'Maison', 'Vieux projets'], array_map(static fn ($todo) => $todo->title, $todos));
        self::assertSame([true, false, false], array_map(static fn ($todo) => $todo->isDefault, $todos));
        self::assertSame($this->todoId(TodoFixtures::ALICE_DEFAULT), $todos[0]->id);
    }

    public function testItListsOneStatus(): void
    {
        $active = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTodosDataInput(TodoStatusRegistry::ACTIVE));
        $archived = $this->useCase->execute($this->currentUser($this->aliceId()), new ListTodosDataInput(TodoStatusRegistry::ARCHIVED));

        self::assertSame(['A faire', 'Maison'], array_map(static fn ($todo) => $todo->title, $active));
        self::assertSame([['Vieux projets', TodoStatusRegistry::ARCHIVED]], array_map(static fn ($todo) => [$todo->title, $todo->status], $archived));
    }

    public function testItRejectsAStatusOutsideTheRegistry(): void
    {
        try {
            $this->useCase->execute($this->currentUser($this->aliceId()), new ListTodosDataInput('deleted'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListTodosValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['status' => ['status_unknown']], $exception->violations);
        }
    }

    public function testListingStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, new ListTodosDataInput()),
        );
    }

    public function testTheQueryCountDoesNotGrowWithTheTodos(): void
    {
        $aliceId = $this->aliceId();
        $create = self::getContainer()->get(CreateTodoUseCase::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, new ListTodosDataInput()),
            function () use ($aliceId, $create): void {
                foreach (['Travail', 'Courses', 'Voyage'] as $title) {
                    $create->execute($this->currentUser($aliceId), new SaveTodoDataInput($title));
                }
            },
        );

        self::assertCount(count($fewer) + 3, $more);
    }
}
