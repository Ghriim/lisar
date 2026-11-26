<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Todo;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\User\UserCreatedEvent;
use App\Domain\DTO\Input\User\RegisterUserDataInput;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Infrastructure\EventHandler\Todo\CreateDefaultTodoEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\User\RegisterUserUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Every account starts with its « A faire ». */
final class CreateDefaultTodoEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use QueryCountTrait;

    public function testItMakesTheAccountsDefaultTodo(): void
    {
        $event = $this->eventRaisedBy(UserCreatedEvent::class, fn () => $this->register());
        $this->handle($event);

        $todo = self::getContainer()->get(TodoProviderGateway::class)->findDefaultForOwner($event->user);
        self::assertNotNull($todo);
        self::assertSame(['A faire', TodoStatusRegistry::ACTIVE, true], [$todo->title, $todo->status, $todo->isDefault]);
    }

    public function testMakingItStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(UserCreatedEvent::class, fn () => $this->register()),
            fn (EventInterface $event) => $this->handle($event),
            // One INSERT, in its transaction.
            max: 3,
        );
    }

    private function handler(): CreateDefaultTodoEventHandler
    {
        return self::getContainer()->get(CreateDefaultTodoEventHandler::class);
    }

    private function register(): void
    {
        self::getContainer()->get(RegisterUserUseCase::class)->execute(new RegisterUserDataInput(
            username: 'carol',
            email: 'carol@lisar.test',
            password: 'Corr3ct-Horse!',
        ));
    }
}
