<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMuscleGroupDataInput;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\UpdateMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Renaming a muscle group. */
final class UpdateMuscleGroupUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MuscleTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MuscleGroupProviderGateway $muscleGroupProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->muscleGroupProviderGateway = self::getContainer()->get(MuscleGroupProviderGateway::class);

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testItRenamesAGroup(): void
    {
        $output = self::getContainer()->get(UpdateMuscleGroupUseCase::class)
            ->execute($this->currentAdmin(), $this->group(MuscleGroupFixtures::OTHER)->id ?? 0, new UpdateMuscleGroupDataInput('Miscellaneous'));

        self::assertSame('Miscellaneous', $output->name);
        self::assertSame('Miscellaneous', $this->muscleGroupProviderGateway->findOneById($output->id)?->name);
    }

    public function testRenamingAGroupStaysWithinItsQueryBudget(): void
    {
        $id = $this->group(MuscleGroupFixtures::OTHER)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(UpdateMuscleGroupUseCase::class)->execute($currentUser, $id, new UpdateMuscleGroupDataInput('Miscellaneous')),
        );
    }
}
