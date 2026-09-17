<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Factory\DataModelFactory\TagDataModelFactory;
use PHPUnit\Framework\TestCase;

final class TagDataModelFactoryTest extends TestCase
{
    private TagDataModelFactory $factory;
    private UserDataModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new TagDataModelFactory();
        $this->owner = new UserDataModel();
        $this->owner->id = 1;
    }

    public function testItBuildsATagForItsOwner(): void
    {
        $tag = $this->factory->buildOne('urgent', $this->owner);

        self::assertSame('urgent', $tag->label);
        self::assertSame($this->owner, $tag->owner);
    }

    public function testItOnlyBuildsTheTagsTheAccountDoesNotHaveYet(): void
    {
        $existing = $this->factory->buildOne('urgent', $this->owner);

        $built = $this->factory->buildMany(['urgent', 'errand'], $this->owner, [$existing]);

        self::assertCount(1, $built);
        self::assertSame('errand', $built[0]->label);
    }

    public function testItBuildsNothingWhenEveryTagExists(): void
    {
        $existing = $this->factory->buildOne('urgent', $this->owner);

        self::assertSame([], $this->factory->buildMany(['urgent'], $this->owner, [$existing]));
    }
}
