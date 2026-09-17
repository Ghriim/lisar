<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\Input\Admin\CreatePriorityDataInput;
use App\Domain\DTO\Input\Admin\UpdatePriorityDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\PriorityProviderGateway;
use App\Domain\Validation\Constraint\Task\DefaultPriorityKeptConstraint;
use App\Domain\Validation\Constraint\Task\PriorityDeletableConstraint;
use App\Domain\Validation\Constraint\Task\PriorityLabelAvailableConstraint;
use App\Fixtures\PriorityFixtures;
use App\Fixtures\TaskFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreatePriorityUseCase;
use App\UseCase\Admin\DeletePriorityUseCase;
use App\UseCase\Admin\UpdatePriorityUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The three write paths on the priority set, together: the rule they enforce — exactly one
 * default, always — is a property of the set and not of any one of them.
 */
final class PriorityBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CreatePriorityUseCase $create;
    private UpdatePriorityUseCase $update;
    private DeletePriorityUseCase $delete;
    private PriorityProviderGateway $priorityProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreatePriorityUseCase::class);
        $this->update = self::getContainer()->get(UpdatePriorityUseCase::class);
        $this->delete = self::getContainer()->get(DeletePriorityUseCase::class);
        $this->priorityProviderGateway = self::getContainer()->get(PriorityProviderGateway::class);
    }

    public function testItCreatesAPriority(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $output = $this->create->execute(new CreatePriorityDataInput('Critique', 5, '#ff00aa'));

        self::assertSame('Critique', $output->label);
        self::assertSame(5, $output->weight);
        self::assertSame('#ff00aa', $output->colour);
        self::assertFalse($output->isDefault);

        // Re-read through the gateway: assert it was really persisted, in weight order.
        self::assertSame(['Critique', 'High', 'Normal', 'Low'], $this->labels());
    }

    /**
     * A task created without a priority has to get something, so the set is never left without
     * a default — including when it is created from nothing.
     */
    public function testTheVeryFirstPriorityIsTheDefaultWhetherAskedOrNot(): void
    {
        $output = $this->create->execute(new CreatePriorityDataInput('Seule', 10, '#ffffff'));

        self::assertTrue($output->isDefault);
    }

    public function testCreatingADefaultTakesItFromWhoeverHadIt(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $this->create->execute(new CreatePriorityDataInput('Critique', 5, '#ff00aa', isDefault: true));

        self::assertSame(['Critique'], $this->defaults());
    }

    public function testUpdatingADefaultTakesItFromWhoeverHadIt(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $high = $this->getReference(PriorityFixtures::HIGH, PriorityDataModel::class);
        $this->update->execute(
            $high->id ?? 0,
            new UpdatePriorityDataInput('High', 10, '#e5484d', isDefault: true),
        );

        self::assertSame(['High'], $this->defaults());
    }

    public function testItRefusesToLeaveTheSetWithoutADefault(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $normal = $this->getReference(PriorityFixtures::NORMAL, PriorityDataModel::class);

        try {
            $this->update->execute(
                $normal->id ?? 0,
                new UpdatePriorityDataInput('Normal', 20, '#3e63dd', isDefault: false),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                DefaultPriorityKeptConstraint::DEFAULT_PRIORITY_REQUIRED,
                $exception->violations['isDefault'],
            );
        }

        self::assertSame(['Normal'], $this->defaults());
    }

    public function testItRefusesALabelAlreadyCarried(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        try {
            $this->create->execute(new CreatePriorityDataInput('High'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                PriorityLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    public function testItDeletesAnUnusedPriority(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $low = $this->getReference(PriorityFixtures::LOW, PriorityDataModel::class);
        $this->delete->execute($low->id ?? 0);

        self::assertSame(['High', 'Normal'], $this->labels());
    }

    public function testItRefusesToDeleteThePriorityTasksCarry(): void
    {
        $this->loadFixtures(TaskFixtures::class);

        $high = $this->getReference(PriorityFixtures::HIGH, PriorityDataModel::class);

        try {
            $this->delete->execute($high->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeletePriorityUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(PriorityDeletableConstraint::PRIORITY_IN_USE, $exception->violations['id']);
        }
    }

    public function testItRefusesToDeleteTheDefault(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $normal = $this->getReference(PriorityFixtures::NORMAL, PriorityDataModel::class);

        try {
            $this->delete->execute($normal->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                PriorityDeletableConstraint::PRIORITY_IS_THE_DEFAULT,
                $exception->violations['id'],
            );
        }
    }

    public function testItFailsOnAnUnknownPriority(): void
    {
        $this->loadFixtures(PriorityFixtures::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->delete->execute(123456789);
    }

    /** @return list<string> */
    private function labels(): array
    {
        return array_map(
            static fn (PriorityDataModel $priority) => $priority->label,
            $this->priorityProviderGateway->findAllOrderedByWeight(),
        );
    }

    /** @return list<string> */
    private function defaults(): array
    {
        $labels = [];
        foreach ($this->priorityProviderGateway->findAllOrderedByWeight() as $priority) {
            if (true === $priority->isDefault) {
                $labels[] = $priority->label;
            }
        }

        return $labels;
    }
}
