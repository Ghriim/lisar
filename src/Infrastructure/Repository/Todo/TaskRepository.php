<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
            ->addOrderBy($this->priorityRank(), 'ASC')
            ->addOrderBy('task.id', 'DESC')
            ->getQuery()
            ->getResult();
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
     * Everything the output carries is fetch-joined here: the state of a task is read off its
     * subtasks, and each of them renders its own category. Lazy loading would
     * turn one list into a few hundred queries.
     */
    private function buildTaskQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('task')
            ->leftJoin('task.category', 'category')
            ->addSelect('category')
            ->leftJoin('task.subtasks', 'subtask')
            ->addSelect('subtask')
            ->leftJoin('subtask.category', 'subtaskCategory')
            ->addSelect('subtaskCategory');
    }
}
