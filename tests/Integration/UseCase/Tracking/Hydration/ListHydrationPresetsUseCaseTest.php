<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationPresetDataInput;
use App\Domain\Registry\Tracking\Hydration\HydrationIconRegistry;
use App\Fixtures\Tracking\Hydration\HydrationPresetFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Hydration\Admin\CreateHydrationPresetUseCase;
use App\UseCase\Tracking\Hydration\ListHydrationPresetsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The drink shortcuts the widget offers, smallest volume first. */
final class ListHydrationPresetsUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHydrationPresetsUseCase $list;
    private CreateHydrationPresetUseCase $create;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHydrationPresetsUseCase::class);
        $this->create = self::getContainer()->get(CreateHydrationPresetUseCase::class);
    }

    public function testItListsTheShortcutsSmallestVolumeFirst(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class);

        self::assertSame([150, 250, 500], array_map(
            static fn ($preset) => $preset->volumeInMillilitres,
            $this->list->execute($this->anyCurrentUser()),
        ));
    }

    public function testListingTheShortcutsStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class);

        $this->assertQueryCountAtMost(
            fn () => $this->anyCurrentUser(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
        );
    }

    public function testListingTheShortcutsDoesNotGrowWithThem(): void
    {
        $this->loadFixtures(HydrationPresetFixtures::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->anyCurrentUser(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
            function (): void {
                $this->create->execute($this->currentAdmin(), new CreateHydrationPresetDataInput(HydrationIconRegistry::CARAFE, 1000));
                $this->create->execute($this->currentAdmin(), new CreateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 330));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }
}
