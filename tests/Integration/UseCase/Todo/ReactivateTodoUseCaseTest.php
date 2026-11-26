<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Todo\TodoUpdatedEvent;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Todo\TodoFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Todo\ReactivateTodoUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReactivateTodoUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use RecordedEventsTrait;
    use SignedInUserTrait;
    use TodoTestTrait;

    private ReactivateTodoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ReactivateTodoUseCase::class);
        $this->loadFixtures(TodoFixtures::class);
    }

    public function testItChangesTheStatusAndSaysSo(): void
    {
        $this->forgetRaisedEvents();
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_ARCHIVED));

        self::assertSame(TodoStatusRegistry::ACTIVE, $output->status);
        self::assertSame(TodoStatusRegistry::ACTIVE, self::getContainer()->get(TodoProviderGateway::class)->findOneById($output->id)?->status);
        self::assertSame(false, $this->assertEventRaised(TodoUpdatedEvent::class)->archives);
    }

    /** Already so: the caller wanted it and it is — nothing is written. */
    public function testItWritesNothingWhenTheTodoIsAlreadySo(): void
    {
        $this->forgetRaisedEvents();
        $output = $this->useCase->execute($this->currentUser($this->aliceId()), $this->todoId(TodoFixtures::ALICE_HOME));

        self::assertSame(TodoStatusRegistry::ACTIVE, $output->status);
        $this->assertNoEventRaised();
    }

    public function testItRefusesSomeoneElsesTodo(): void
    {
        try {
            $this->useCase->execute($this->currentUser($this->adminId()), $this->todoId(TodoFixtures::ALICE_ARCHIVED));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testATodoThatDoesNotExistIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute($this->currentUser($this->aliceId()), 999999);
    }

    public function testItStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $todoId = $this->todoId(TodoFixtures::ALICE_ARCHIVED);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $todoId),
        );
    }
}
