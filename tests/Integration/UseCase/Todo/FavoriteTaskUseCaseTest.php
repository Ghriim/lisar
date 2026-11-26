<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TaskFavoritableConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Fixtures\Todo\TaskFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\FavoriteTaskUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FavoriteTaskUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private FavoriteTaskUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(FavoriteTaskUseCase::class);
        $this->loadFixtures(TaskFixtures::class);
    }

    public function testItSetsTheStar(): void
    {
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->taskId(TaskFixtures::ALICE_PLAIN));

        self::assertSame(true, $output->isFavorite);
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertSame(true, self::getContainer()->get(TaskProviderGateway::class)->findOneById($output->id)?->isFavorite);
    }

    /** Twice is once: the second call finds it so already. */
    public function testItIsIdempotent(): void
    {
        $this->useCase->execute($this->currentUser($this->aliceId()), $this->taskId(TaskFixtures::ALICE_PLAIN));
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->taskId(TaskFixtures::ALICE_PLAIN));

        self::assertSame(true, $output->isFavorite);
    }

    public function testItRefusesASubtask(): void
    {
        $this->assertRefused(['id' => [TaskFavoritableConstraint::SUBTASK_NOT_FAVORITABLE]], $this->taskId(TaskFixtures::ALICE_SUBTASK_OPEN), $this->aliceId());
    }

    /** Nothing in an archived TODO changes, its stars included. */
    public function testItRefusesATaskInAnArchivedTodo(): void
    {
        $this->assertRefused(['id' => [TodoActiveConstraint::TODO_ARCHIVED]], $this->taskId(TaskFixtures::ALICE_ARCHIVED), $this->aliceId());
    }

    public function testItRefusesSomeoneElsesTask(): void
    {
        $this->assertRefused(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $this->taskId(TaskFixtures::ALICE_PLAIN), $this->adminId());
    }

    public function testATaskThatDoesNotExistIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($this->currentUser($this->aliceId()), 999999);
    }

    public function testItStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $taskId = $this->taskId(TaskFixtures::ALICE_PLAIN);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $taskId),
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, int $taskId, int $callerId): void
    {
        try {
            $this->useCase->execute($this->currentUser($callerId), $taskId);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame($violations, $exception->violations);
        }
    }
}
