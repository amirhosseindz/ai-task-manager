<?php

namespace App\Task\Entity;

use App\Task\Repository\TaskRepository;
use App\Task\Enum\TaskPriority;
use App\Task\Enum\TaskStatus;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['task:read', 'task:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Task title cannot be empty')]
    #[Assert\Length(max: 255, maxMessage: 'Task title cannot be longer than 255 characters')]
    #[Groups(['task:read', 'task:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 20, type: 'string', enumType: TaskPriority::class)]
    #[Groups(['task:read', 'task:write'])]
    private TaskPriority $priority;

    #[ORM\Column(length: 20, type: 'string', enumType: TaskStatus::class)]
    #[Groups(['task:read', 'task:write'])]
    private TaskStatus $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Task must have a user ID')]
    #[Groups(['task:read', 'task:write'])]
    private ?int $userId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->priority = TaskPriority::Medium;
        $this->status = TaskStatus::TODO;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOverdue(): bool
    {
        return $this->dueDate && $this->dueDate < new \DateTimeImmutable() && $this->status !== TaskStatus::DONE;
    }

    public function isCompleted(): bool
    {
        return $this->status === TaskStatus::DONE;
    }

    public function markAsCompleted(): self
    {
        $this->status = TaskStatus::DONE;

        return $this;
    }

    public function toArray(NormalizerInterface $normalizer): array
    {
        return $normalizer->normalize($this, null, ['groups' => ['task:read']]);
    }
}
