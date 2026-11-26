<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class TodoOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param TodoDataModel[] $todos
     *
     * @return list<TodoDataOutput>
     */
    public function buildMany(array $todos): array
    {
        $outputs = [];
        foreach ($todos as $todo) {
            $outputs[] = $this->buildOne($todo);
        }

        return $outputs;
    }

    public function buildOne(TodoDataModel $todo): TodoDataOutput
    {
        return $this->mapper->map($todo, TodoDataOutput::class);
    }
}
