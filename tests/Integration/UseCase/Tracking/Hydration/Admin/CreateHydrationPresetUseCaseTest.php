<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationPresetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Tracking\Hydration\HydrationIconRegistry;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Hydration\Admin\CreateHydrationPresetUseCase;
use App\UseCase\Tracking\Hydration\ListHydrationPresetsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Adding a drink shortcut to the widget. */
final class CreateHydrationPresetUseCaseTest extends KernelTestCase
{
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

    public function testItCreatesAShortcut(): void
    {
        $output = $this->create->execute(
            $this->currentAdmin(),
            new CreateHydrationPresetDataInput(HydrationIconRegistry::CARAFE, 1000),
        );

        self::assertSame(HydrationIconRegistry::CARAFE, $output->icon);
        self::assertSame(1000, $output->volumeInMillilitres);
        self::assertCount(1, $this->list->execute($this->anyCurrentUser()));
    }

    public function testItRefusesAnIconTheFrontEndsCouldNotDraw(): void
    {
        try {
            $this->create->execute($this->currentAdmin(), new CreateHydrationPresetDataInput('aquarium', 1000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('icon', $exception->violations);
        }
    }

    public function testCreatingAShortcutStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->create->execute($currentUser, new CreateHydrationPresetDataInput(HydrationIconRegistry::CARAFE, 1000)),
        );
    }
}
