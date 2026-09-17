<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Factory\OutputFactory\PriorityOutputFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;

final class PriorityOutputFactoryTest extends TestCase
{
    private PriorityOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new PriorityOutputFactory(new ObjectMapper());
    }

    public function testItBuildsThePriority(): void
    {
        $output = $this->factory->buildOne($this->buildPriority('High', 10, '#e5484d', false));

        self::assertSame(1, $output->id);
        self::assertSame('High', $output->label);
        self::assertSame(10, $output->weight);
        self::assertSame('#e5484d', $output->colour);
        self::assertFalse($output->isDefault);
    }

    public function testItBuildsManyOutputs(): void
    {
        $outputs = $this->factory->buildMany([
            $this->buildPriority('High', 10, '#e5484d', false),
            $this->buildPriority('Normal', 20, '#3e63dd', true, id: 2),
        ]);

        self::assertCount(2, $outputs);
        self::assertTrue($outputs[1]->isDefault);
    }

    private function buildPriority(
        string $label,
        int $weight,
        string $colour,
        bool $isDefault,
        int $id = 1,
    ): PriorityDataModel {
        $priority = new PriorityDataModel();
        $priority->id = $id;
        $priority->label = $label;
        $priority->weight = $weight;
        $priority->colour = $colour;
        $priority->isDefault = $isDefault;

        return $priority;
    }
}
