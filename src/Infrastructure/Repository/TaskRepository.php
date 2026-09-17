<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskDataModel>
 */
final class TaskRepository extends ServiceEntityRepository implements TaskProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskDataModel::class);
    }

    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?TaskDataModel
    {
        return $this->buildTaskQuery()
            ->andWhere('task.id = :id')
            ->andWhere('task.owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TaskDataModel> */
    public function findAllForOwnerList(UserDataModel $owner, bool $isDone): array
    {
        $queryBuilder = $this->buildTaskQuery()
            ->andWhere('task.owner = :owner')
            // Root tasks only: subtasks come back nested under their parent.
            ->andWhere('task.parent IS NULL')
            ->setParameter('owner', $owner);

        $queryBuilder->andWhere(
            true === $isDone ? 'task.completedAt IS NOT NULL' : 'task.completedAt IS NULL',
        );

        // The default order: by category, then by priority within a category. A task without
        // either sorts last rather than first, which is what a person expects to see.
        return $queryBuilder
            ->addOrderBy('CASE WHEN category.id IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('category.label', 'ASC')
            ->addOrderBy('CASE WHEN priority.id IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('priority.weight', 'ASC')
            ->addOrderBy('task.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countForPriority(PriorityDataModel $priority): int
    {
        return (int) $this->createQueryBuilder('task')
            ->select('COUNT(task.id)')
            ->andWhere('task.priority = :priority')
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * Everything the output carries is fetch-joined here: the state of a task is read off its
     * subtasks, and each of them renders its own priority, category and tags. Lazy loading would
     * turn one list into a few hundred queries.
     */
    private function buildTaskQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('task')
            ->leftJoin('task.priority', 'priority')
            ->addSelect('priority')
            ->leftJoin('task.category', 'category')
            ->addSelect('category')
            ->leftJoin('task.tags', 'tag')
            ->addSelect('tag')
            ->leftJoin('task.subtasks', 'subtask')
            ->addSelect('subtask')
            ->leftJoin('subtask.priority', 'subtaskPriority')
            ->addSelect('subtaskPriority')
            ->leftJoin('subtask.category', 'subtaskCategory')
            ->addSelect('subtaskCategory')
            ->leftJoin('subtask.tags', 'subtaskTag')
            ->addSelect('subtaskTag');
    }
}
