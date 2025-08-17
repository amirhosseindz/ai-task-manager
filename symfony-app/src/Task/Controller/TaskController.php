<?php

namespace App\Task\Controller;

use App\Task\Entity\Task;
use App\Task\Enum\TaskPriority;
use App\Task\Enum\TaskStatus;
use App\Task\Repository\TaskRepository;
use App\Task\Service\NatsPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskRepository $taskRepository,
        private SerializerInterface $serializer,
        private NormalizerInterface $normalizer,
        private ValidatorInterface $validator,
        private NatsPublisher $natsPublisher
    ) {}

    #[Route('', name: 'task_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // For now, we'll use a hardcoded user ID
        // In a real app, this would come from JWT token
        $userId = (int) $request->query->get('user_id', 1);

        $filters = [
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'due_from' => $request->query->get('due_from'),
            'due_to' => $request->query->get('due_to'),
            'search' => $request->query->get('search'),
            'sort_by' => $request->query->get('sort_by', 'createdAt'),
            'sort_direction' => $request->query->get('sort_direction', 'DESC'),
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $tasks = $this->taskRepository->findByUser($userId, $filters);

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn(Task $task) => $task->toArray($this->normalizer), $tasks),
            'meta' => [
                'total' => count($tasks),
                'filters_applied' => !empty($filters)
            ]
        ]);
    }

    #[Route('/{id}', name: 'task_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'status' => 'success',
            'data' => $task->toArray($this->normalizer)
        ]);
    }

    #[Route('', name: 'task_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }

        $task = new Task();
        
        // Set basic properties
        $task->setTitle($data['title'] ?? '');
        $task->setDescription($data['description'] ?? null);
        $task->setPriority(TaskPriority::tryFrom($data['priority']) ?? TaskPriority::Medium);
        $task->setStatus(TaskStatus::tryFrom($data['status']) ?? TaskStatus::TODO);
        $task->setUserId($data['user_id'] ?? 1); // Default user for now

        // Handle due date
        if (!empty($data['due_date'])) {
            try {
                $dueDate = new \DateTimeImmutable($data['due_date']);
                $task->setDueDate($dueDate);
            } catch (\Exception $e) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid due date format. Use Y-m-d H:i:s format.'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate the task
        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->taskRepository->save($task, true);

            // Publish task created event
            $this->natsPublisher->publishTaskEvent('created', $task->toArray($this->normalizer));

            return $this->json([
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => $task->toArray($this->normalizer)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to create task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'task_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }

        $oldStatus = $task->getStatus();

        // Update properties if provided
        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        if (isset($data['priority'])) {
            $task->setPriority(TaskPriority::from($data['priority']));
        }

        if (isset($data['status'])) {
            $task->setStatus(TaskStatus::from($data['status']));
        }

        if (isset($data['due_date'])) {
            if ($data['due_date'] === null) {
                $task->setDueDate(null);
            } else {
                try {
                    $dueDate = new \DateTimeImmutable($data['due_date']);
                    $task->setDueDate($dueDate);
                } catch (\Exception $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Invalid due date format'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        // Validate the updated task
        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->taskRepository->save($task, true);

            // Publish appropriate events
            $this->natsPublisher->publishTaskEvent('updated', $task->toArray($this->normalizer));

            // Special event if task was completed
            if ($oldStatus !== TaskStatus::DONE && $task->getStatus() === TaskStatus::DONE) {
                $this->natsPublisher->publishTaskEvent('completed', $task->toArray($this->normalizer));
            }

            return $this->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $task->toArray($this->normalizer)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to update task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'task_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $taskData = $task->toArray($this->normalizer); // Get data before deletion
            $this->taskRepository->remove($task, true);

            // Publish task deleted event
            $this->natsPublisher->publishTaskEvent('deleted', $taskData);

            return $this->json([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to delete task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stats', name: 'task_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $userId = (int) $request->query->get('user_id', 1);
        
        $stats = $this->taskRepository->getTaskStats($userId);
        $overdueTasks = $this->taskRepository->findOverdueByUser($userId);
        $dueSoonTasks = $this->taskRepository->findDueSoon($userId, 7);

        return $this->json([
            'status' => 'success',
            'data' => [
                'overview' => $stats,
                'overdue_tasks' => array_map(fn(Task $task) => $task->toArray($this->normalizer), $overdueTasks),
                'due_soon_tasks' => array_map(fn(Task $task) => $task->toArray($this->normalizer), $dueSoonTasks)
            ]
        ]);
    }

    #[Route('/health', name: 'task_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'healthy',
            'service' => 'task-service',
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ]);
    }
}
