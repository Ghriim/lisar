<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Gamification;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Gamification\StatusDataOutput;
use App\Fixtures\Gamification\ExperienceGainFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Gamification\GetStatusUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Alice has earned Force 320, Endurance 60 and Intelligence 40; Bob nothing. */
final class GetStatusUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(ExperienceGainFixtures::class);
    }

    public function testItGivesEachStatsLevelAndTheGlobalOne(): void
    {
        $status = $this->statusOf(UserFixtures::ALICE);

        self::assertSame([3, 420], [$status->progress->level, $status->progress->experience]);
        self::assertSame(
            ['strength' => 3, 'endurance' => 2, 'agility' => 1, 'intelligence' => 1, 'spirit' => 1, 'willpower' => 1],
            $this->levels($status),
        );
    }

    /** Nothing earned yet: every stat at level 1 with 0 XP, and the global level too. */
    public function testSomeoneWithNoExperienceStandsAtLevelOne(): void
    {
        $status = $this->statusOf(UserFixtures::BOB_DEACTIVATED);

        self::assertSame([1, 0, 0, 50], [$status->progress->level, $status->progress->experience, $status->progress->experienceIntoLevel, $status->progress->experienceForNextLevel]);
        self::assertSame([1, 1, 1, 1, 1, 1], array_values($this->levels($status)));
    }

    public function testReadingTheStatusStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf(UserFixtures::ALICE)),
            fn (UserDataModel $currentUser) => self::getContainer()->get(GetStatusUseCase::class)->execute($currentUser),
            // The sums by stat, in one query, whatever the number of gains.
            max: 1,
        );
    }

    private function statusOf(string $user): StatusDataOutput
    {
        return self::getContainer()->get(GetStatusUseCase::class)->execute($this->currentUser($this->idOf($user)));
    }

    private function idOf(string $user): int
    {
        return $this->getReference($user, UserDataModel::class)->id ?? 0;
    }

    /** @return array<string, int> */
    private function levels(StatusDataOutput $status): array
    {
        $levels = [];
        foreach ($status->groups as $group) {
            foreach ($group->stats as $stat) {
                $levels[$stat->stat] = $stat->progress->level;
            }
        }

        return $levels;
    }
}
