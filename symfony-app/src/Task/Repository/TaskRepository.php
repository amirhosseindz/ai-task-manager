<?php

namespace App\Task\Repository;

use App\Task\Entity\Task;
use App\Task\Enum\TaskPriority;
use App\Task\Enum\TaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function save(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find tasks by user ID with optional filtering
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId);

        // Filter by status
        if (isset($filters['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Filter by priority
        if (isset($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        // Filter by due date range
        if (isset($filters['due_from'])) {
            $qb->andWhere('t.dueDate >= :due_from')
               ->setParameter('due_from', new \DateTimeImmutable($filters['due_from']));
        }

        if (isset($filters['due_to'])) {
            $qb->andWhere('t.dueDate <= :due_to')
               ->setParameter('due_to', new \DateTimeImmutable($filters['due_to']));
        }

        // Search in title and description
        if (isset($filters['search'])) {
            $qb->andWhere('(t.title LIKE :search OR t.description LIKE :search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'createdAt';
        $sortDirection = strtoupper($filters['sort_direction'] ?? 'DESC');

        if (in_array($sortDirection, ['ASC', 'DESC'])) {
            $qb->orderBy('t.' . $sortBy, $sortDirection);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find overdue tasks for a user
     */
    public function findOverdueByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->andWhere('t.dueDate < :now')
            ->andWhere('t.status != :completed')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('completed', TaskStatus::DONE)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get task statistics for a user
     */
    public function getTaskStats(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = :todo THEN 1 ELSE 0 END) as todo_count,
                SUM(CASE WHEN status = :in_progress THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = :done THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN due_date < NOW() AND status != :done THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN priority = :high THEN 1 ELSE 0 END) as high_priority_count
            FROM task 
            WHERE user_id = :userId
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'userId' => $userId,
            'todo' => TaskStatus::TODO,
            'in_progress' => TaskStatus::IN_PROGRESS,
            'done' => TaskStatus::DONE,
            'high' => TaskPriority::High,
        ]);

        return $result->fetchAssociative();
    }

    /**
     * Find tasks due within the next X days
     */
    public function findDueSoon(int $userId, int $days = 7): array
    {
        $dueSoonDate = new \DateTimeImmutable("+{$days} days");
        
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->andWhere('t.dueDate <= :dueSoon')
            ->andWhere('t.dueDate >= :now')
            ->andWhere('t.status != :completed')
            ->setParameter('userId', $userId)
            ->setParameter('dueSoon', $dueSoonDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('completed', TaskStatus::DONE)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
