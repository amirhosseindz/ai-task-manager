# AI-Powered Task Management System (Restructured)

A consolidated Symfony 7 application with intelligent AI agent integration for task management through natural language interactions.

## 🎯 Project Goals

This restructured project demonstrates:
- **Modular Symfony Architecture** with domain separation
- **Event-driven Communication** using Symfony Messenger
- **AI Integration** with Python and MCP (Model Context Protocol) servers
- **Clean separation of concerns** within a single application

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Symfony App                              │
│                   (Port 8000)                               │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐   │
│  │   User      │ │    Task     │ │    Notification     │   │
│  │  Module     │ │   Module    │ │      Module         │   │
│  │             │ │             │ │                     │   │
│  └─────────────┘ └─────────────┘ └─────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │           Symfony Messenger                         │   │
│  │         (Event Bus / Message Bus)                   │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                               │
                   ┌─────────────────┐
                   │   AI Agent      │
                   │  (Port 8004)    │
                   │   Python +      │
                   │     MCP         │
                   └─────────────────┘
```

## 📁 New Project Structure

```
ai-task-manager/
├── symfony-app/                    # Single Symfony 7 application
│   ├── src/
│   │   ├── User/                   # User domain
│   │   │   ├── Controller/
│   │   │   ├── Entity/
│   │   │   ├── Repository/
│   │   │   ├── Service/
│   │   │   └── EventSubscriber/
│   │   ├── Task/                   # Task domain
│   │   │   ├── Controller/
│   │   │   ├── Entity/
│   │   │   ├── Repository/
│   │   │   ├── Service/
│   │   │   └── EventSubscriber/
│   │   ├── Notification/           # Notification domain
│   │   │   ├── Controller/
│   │   │   ├── Service/
│   │   │   └── EventSubscriber/
│   │   ├── Shared/                 # Shared components
│   │   │   ├── Event/
│   │   │   ├── Service/
│   │   │   └── Messenger/
│   │   └── Kernel.php
│   ├── config/
│   │   ├── packages/
│   │   ├── routes/
│   │   │   ├── user.yaml
│   │   │   ├── task.yaml
│   │   │   └── notification.yaml
│   │   └── services.yaml
│   ├── migrations/
│   ├── public/
│   ├── templates/
│   ├── .env
│   └── composer.json
├── ai-agent/                       # Python AI agent (unchanged)
├── docker-compose.yml              # PostgreSQL, Redis only
├── .env                           # Infrastructure secrets
├── setup-env.sh                   # Updated setup script
└── docs/
```

## 🚀 Key Changes & Benefits

### Benefits of Consolidation:
- ✅ **Simpler Deployment** - Single application to deploy
- ✅ **Shared Database** - No need for distributed transactions
- ✅ **Faster Development** - No inter-service communication overhead
- ✅ **Easier Debugging** - All logs in one place
- ✅ **Better Performance** - No HTTP overhead between modules
- ✅ **Simplified Configuration** - Single .env file

### Domain Separation Maintained:
- 🏗️ **User Module** - Authentication, profiles, user management
- 📋 **Task Module** - Task CRUD, priorities, assignments
- 📧 **Notification Module** - Email/SMS notifications
- 🤖 **AI Integration** - Natural language task processing

## 🛠️ Migration Steps

### 1. Create New Symfony Project

```bash
# Create new Symfony application
composer create-project symfony/skeleton symfony-app
cd symfony-app

# Install required packages
composer require symfony/orm-pack
composer require symfony/messenger
composer require symfony/mailer
composer require symfony/security-bundle
composer require symfony/serializer-pack
composer require nelmio/api-doc-bundle
composer require symfony/validator
```

### 2. Set Up Domain Structure

```bash
# Create domain directories
mkdir -p src/User/{Controller,Entity,Repository,Service,EventSubscriber}
mkdir -p src/Task/{Controller,Entity,Repository,Service,EventSubscriber}  
mkdir -p src/Notification/{Controller,Service,EventSubscriber}
mkdir -p src/Shared/{Event,Service,Messenger}
```

### 3. Configure Routing by Domain

```yaml
# config/routes/user.yaml
user_api:
    resource: '../src/User/Controller/'
    type: annotation
    prefix: /api/users

# config/routes/task.yaml  
task_api:
    resource: '../src/Task/Controller/'
    type: annotation
    prefix: /api/tasks

# config/routes/notification.yaml
notification_api:
    resource: '../src/Notification/Controller/'
    type: annotation
    prefix: /api/notifications
```

### 4. Configure Symfony Messenger

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        default_bus: command.bus
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
            event.bus:
                default_middleware: allow_no_handlers
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
            events: 'in-memory://'
        routing:
            'App\Shared\Event\*': events
            'App\Task\Message\*': async
            'App\User\Message\*': async
```

## 🔧 Implementation Example

### Shared Event System

```php
<?php
// src/Shared/Event/TaskCreatedEvent.php
namespace App\Shared\Event;

class TaskCreatedEvent
{
    public function __construct(
        private int $taskId,
        private int $userId,
        private string $title,
        private ?\DateTimeInterface $dueDate = null
    ) {}
    
    // getters...
}
```

### Task Controller

```php
<?php
// src/Task/Controller/TaskController.php
namespace App\Task\Controller;

use App\Shared\Event\TaskCreatedEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $eventBus
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Create task logic...
        
        // Dispatch event
        $this->eventBus->dispatch(new TaskCreatedEvent(
            $task->getId(),
            $task->getUserId(), 
            $task->getTitle(),
            $task->getDueDate()
        ));
        
        return $this->json($task);
    }
}
```

### Notification Event Subscriber

```php
<?php
// src/Notification/EventSubscriber/TaskEventSubscriber.php
namespace App\Notification\EventSubscriber;

use App\Shared\Event\TaskCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TaskEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TaskCreatedEvent::class => 'onTaskCreated',
        ];
    }
    
    public function onTaskCreated(TaskCreatedEvent $event): void
    {
        // Send notification logic...
        // Can also dispatch to AI agent via HTTP
    }
}
```

## 🏃‍♂️ New Running Instructions

### 1. Quick Setup
```bash
git clone https://github.com/amirhosseindz/ai-task-manager.git
cd ai-task-manager

# Run setup script
./setup-env.sh

# Start infrastructure (just PostgreSQL and Redis now)
docker-compose up -d
```

### 2. Install Dependencies
```bash
cd symfony-app
composer install

cd ../ai-agent
uv pip install -r requirements.txt
```

### 3. Database Setup
```bash
cd symfony-app
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Start Applications
```bash
# Terminal 1: Symfony App
cd symfony-app
symfony server:start --port=8000

# Terminal 2: AI Agent  
cd ai-agent
uvicorn main:app --host 0.0.0.0 --port=8004 --reload
```

## 🧪 Testing Strategy

```bash
# Single test command for all domains
cd symfony-app
php bin/phpunit

# Domain-specific tests
php bin/phpunit tests/User/
php bin/phpunit tests/Task/
php bin/phpunit tests/Notification/
```

## 🔗 AI Agent Integration

The AI agent remains separate but integrates via:
- **HTTP API calls** to Symfony endpoints
- **Database direct access** for analytics
- **Webhook notifications** for real-time updates

```python
# ai-agent integration example
async def create_task_from_nl(user_input: str, user_id: int):
    task_data = await parse_natural_language(user_input)
    
    # Call Symfony API
    async with httpx.AsyncClient() as client:
        response = await client.post(
            "http://localhost:8000/api/tasks",
            json=task_data,
            headers={"X-User-ID": str(user_id)}
        )
    
    return response.json()
```

## 📚 API Documentation

- **Single API docs**: http://localhost:8000/api/doc
- **All endpoints** documented in one place
- **Domain-organized** but unified documentation

This restructure gives you all the benefits of domain separation while keeping the simplicity of a single application perfect for a weekend project!