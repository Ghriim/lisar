<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\CompleteHabitUseCase;
use App\UseCase\Habits\UncompleteHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Un-ticking a manual habit for today. A tracker habit is unkept by its tracker, never by hand. */
final class UncompleteHabitUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CompleteHabitUseCase $complete;
    private UncompleteHabitUseCase $uncomplete;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complete = self::getContainer()->get(CompleteHabitUseCase::class);
        $this->uncomplete = self::getContainer()->get(UncompleteHabitUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testUnTickingAManualHabitClearsToday(): void
    {
        $reading = $this->habitId(HabitFixtures::READING);

        $this->complete->execute($this->currentUser($this->idOf($this->alice)), $reading);
        $output = $this->uncomplete->execute($this->currentUser($this->idOf($this->alice)), $reading);

        self::assertFalse($output->isCompletedToday);
        self::assertFalse($output->days[HabitOutputFactory::WINDOW_IN_DAYS - 1]->isCompleted);
    }

    /** Un-ticking a day that was never kept changes nothing. */
    public function testUnTickingADayNeverKeptIsANoOp(): void
    {
        $output = $this->uncomplete->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::READING));

        self::assertFalse($output->isCompletedToday);
    }

    public function testATrackerHabitIsNotUnTickedByHand(): void
    {
        $this->expectException(ValidationException::class);

        $this->uncomplete->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::WALK));
    }

    public function testUncompletingAHabitStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $reading = $this->habitId(HabitFixtures::READING);
        $this->complete->execute($this->currentUser($aliceId), $reading);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->uncomplete->execute($currentUser, $reading),
        );
    }
}
