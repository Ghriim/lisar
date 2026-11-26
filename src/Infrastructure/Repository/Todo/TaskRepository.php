<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Todo;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function count;
use function sprintf;

/**
 * @extends ServiceEntityRepository<TaskDataModel>
 */
final class TaskRepository extends ServiceEntityRepository implements TaskProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskDataModel::class);
    }

    public function findOneById(int $id): ?TaskDataModel
    {
        return $this->buildTaskQuery()
            ->andWhere('task.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TaskDataModel> */
    public function findAllForOwnerList(UserDataModel $owner, TodoDataModel $todo, bool $isDone): array
    {
        $queryBuilder = $this->buildTaskQuery()
            ->andWhere('task.owner = :owner')
            ->andWhere('task.todo = :todo')
            // Root tasks only: subtasks come back nested under their parent.
            ->andWhere('task.parent IS NULL')
            ->setParameter('owner', $owner)
            ->setParameter('todo', $todo);

        $queryBuilder->andWhere(
            true === $isDone ? 'task.completedAt IS NOT NULL' : 'task.completedAt IS NULL',
        );

        return $this->inListOrder($queryBuilder)->getQuery()->getResult();
    }

    /** @return list<TaskDataModel> */
    public function findOverviewForOwner(UserDataModel $owner, DateTimeImmutable $today, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $queryBuilder = $this->buildTaskQuery()
            ->andWhere('task.owner = :owner')
            ->andWhere('task.parent IS NULL')
            // An archived TODO's tasks show nowhere.
            ->andWhere('todo.status = :active')
            ->andWhere(
                '(task.completedAt IS NULL AND (task.dueDate <= :today OR task.isFavorite = true))'
                .' OR (task.completedAt >= :from AND task.completedAt < :to AND (task.dueDate <= :today OR task.isFavorite = true))',
            )
            ->setParameter('owner', $owner)
            ->setParameter('active', TodoStatusRegistry::ACTIVE)
            ->setParameter('today', $today, Types::DATE_IMMUTABLE)
            ->setParameter('from', $from, Types::DATETIME_IMMUTABLE)
            ->setParameter('to', $to, Types::DATETIME_IMMUTABLE);

        return $this->inListOrder($queryBuilder)->getQuery()->getResult();
    }

    /**
     * The default order: by category, then by priority within a category. A task without either
     * sorts last rather than first, which is what a person expects to see.
     */
    private function inListOrder(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->addOrderBy('CASE WHEN category.id IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('category.label', 'ASC')
            ->addOrderBy($this->priorityRank(), 'ASC')
            ->addOrderBy('task.id', 'DESC');
    }

    public function countForCategory(CategoryDataModel $category): int
    {
        return (int) $this->createQueryBuilder('task')
            ->select('COUNT(task.id)')
            ->andWhere('task.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<TaskDataModel> */
    public function findPlannedForOwnerBetween(UserDataModel $owner, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // The entry is fetch-joined for the planning to place each task; nothing else of the task
        // is read there.
        return $this->createQueryBuilder('task')
            ->innerJoin('task.planningEntry', 'entry')
            ->addSelect('entry')
            ->andWhere('task.owner = :owner')
            ->andWhere('task.plannedDate BETWEEN :from AND :to')
            ->setParameter('owner', $owner)
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->orderBy('task.plannedDate', 'ASC')
            ->addOrderBy('task.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TaskDataModel> */
    public function findOpenForPlanningEntry(TaskPlanningEntryDataModel $entry): array
    {
        return $this->createQueryBuilder('task')
            ->andWhere('task.planningEntry = :entry')
            ->andWhere('task.completedAt IS NULL')
            ->setParameter('entry', $entry)
            ->getQuery()
            ->getResult();
    }

    /** @return list<string> */
    public function findPlannedDatesForPlanningEntry(TaskPlanningEntryDataModel $entry, array $days): array
    {
        if ([] === $days) {
            return [];
        }

        $rows = $this->createQueryBuilder('task')
            ->select('task.plannedDate')
            ->andWhere('task.planningEntry = :entry')
            ->andWhere('task.plannedDate IN (:days)')
            ->setParameter('entry', $entry)
            ->setParameter('days', array_map(static fn (DateTimeImmutable $day): string => $day->format('Y-m-d'), $days), ArrayParameterType::STRING)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn (array $row): string => $row['plannedDate']->format('Y-m-d'), $rows));
    }

    /** @return list<int> */
    public function findPlanningEntryIdsPlannedOn(DateTimeImmutable $day): array
    {
        $rows = $this->createQueryBuilder('task')
            ->select('IDENTITY(task.planningEntry) AS entryId')
            ->andWhere('task.planningEntry IS NOT NULL')
            ->andWhere('task.plannedDate = :day')
            ->setParameter('day', $day, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn (array $row): int => (int) $row['entryId'], $rows));
    }

    /**
     * The priority as a rank, in the registry's order: a code sorted as a word would put « low »
     * before « normal ». A task without one comes last.
     */
    private function priorityRank(): string
    {
        $whens = '';
        foreach (TaskPriorityRegistry::ALL as $rank => $priority) {
            $whens .= sprintf(" WHEN task.priority = '%s' THEN %d", $priority, $rank);
        }

        return sprintf('CASE%s ELSE %d END', $whens, count(TaskPriorityRegistry::ALL));
    }

    /**
     * Everything the output and the validators read is fetch-joined here: the state of a task is
     * read off its subtasks, each of them renders its own category, and whether it may change is
     * read off its TODO. Lazy loading would
     * turn one list into a few hundred queries.
     */
    private function buildTaskQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('task')
            ->innerJoin('task.todo', 'todo')
            ->addSelect('todo')
            ->leftJoin('task.category', 'category')
            ->addSelect('category')
            ->leftJoin('task.subtasks', 'subtask')
            ->addSelect('subtask')
            ->leftJoin('subtask.category', 'subtaskCategory')
            ->addSelect('subtaskCategory');
    }
}
