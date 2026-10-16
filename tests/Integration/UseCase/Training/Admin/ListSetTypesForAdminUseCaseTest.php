<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListSetTypesForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use App\Fixtures\Training\SetTypeFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateSetTypeUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\Admin\ListSetTypesForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Listing the set types for the back-office, filtered by status. */
final class ListSetTypesForAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SetTypeTestTrait;
    use SignedInUserTrait;

    /** How many set types SetTypeFixtures seeds. */
    private const int SEEDED = 5;

    private ListSetTypesForAdminUseCase $list;
    private CreateSetTypeUseCase $create;
    private DeactivateSetTypeUseCase $deactivate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListSetTypesForAdminUseCase::class);
        $this->create = self::getContainer()->get(CreateSetTypeUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateSetTypeUseCase::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItListsEverySetTypeByName(): void
    {
        self::assertSame(['Back-off', 'Dropset', 'Échauffement', 'Échec', 'Travail'], $this->names(new ListSetTypesForAdminDataInput()));
    }

    public function testTheListFiltersByStatus(): void
    {
        $this->deactivate->execute($this->currentAdmin(), $this->dropset()->id ?? 0);

        self::assertCount(self::SEEDED, $this->list->execute($this->currentAdmin(), new ListSetTypesForAdminDataInput()));
        self::assertCount(self::SEEDED - 1, $this->list->execute($this->currentAdmin(), new ListSetTypesForAdminDataInput(isActive: true)));
        self::assertSame(['Dropset'], $this->names(new ListSetTypesForAdminDataInput(isActive: false)));
    }

    public function testListingTheSetTypesStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser, new ListSetTypesForAdminDataInput()),
        );
    }

    public function testListingTheSetTypesDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser, new ListSetTypesForAdminDataInput()),
            function (): void {
                $this->create->execute($this->currentAdmin(), new CreateSetTypeDataInput('Rest-pause', SetTypeColourRegistry::TEAL));
                $this->create->execute($this->currentAdmin(), new CreateSetTypeDataInput('Cluster', SetTypeColourRegistry::PINK));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }

    /** @return list<string> */
    private function names(ListSetTypesForAdminDataInput $input): array
    {
        return array_map(static fn ($setType) => $setType->name, $this->list->execute($this->currentAdmin(), $input));
    }
}
