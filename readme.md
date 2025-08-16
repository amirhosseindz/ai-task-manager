# AI-Powered Task Management System

A distributed microservices-based task management system with an intelligent AI agent that helps users create, prioritize, and manage tasks through natural language interactions.

## 🎯 Project Goals

This project demonstrates modern software architecture patterns including:
- **Microservices Architecture** with Symfony 7
- **Event-driven Communication** using NATS pub/sub
- **AI Integration** with Python and MCP (Model Context Protocol) servers
- **Real-time Collaboration** and intelligent task management

## 🏗️ System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   API Gateway   │    │  User Service   │    │  Task Service   │
│   (Port 8000)   │    │   (Port 8001)   │    │   (Port 8002)   │
│   Symfony 7     │    │   Symfony 7     │    │   Symfony 7     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                      │                        │
         └──────────────────────┼────────────────────────┘
                                │
    ┌─────────────────┐         │         ┌─────────────────┐
    │ Notification    │         │         │   AI Agent      │
    │   Service       │         │         │  (Port 8004)    │
    │ (Port 8003)     │         │         │   Python +      │
    │  Symfony 7      │         │         │     MCP         │
    └─────────────────┘         │         └─────────────────┘
                                │
                    ┌─────────────────┐
                    │  NATS Server    │
                    │  (Pub/Sub)      │
                    │  Port 4222      │
                    └─────────────────┘
```

## 🚀 Key Features

- **Natural Language Task Creation**: "Hey AI, add a task to review the quarterly report by Friday"
- **Smart Task Prioritization**: AI analyzes deadlines and importance
- **Real-time Notifications**: Cross-service event-driven updates
- **Microservices Communication**: Services communicate via NATS pub/sub
- **AI-Powered Insights**: Task analytics and productivity suggestions

## 📋 Prerequisites

Make sure you have the following installed:
- **macOS** (this guide is macOS-specific)
- **Python 3** and **UV** package manager
- **PHP 8.4** and **Composer**
- **Symfony CLI**
- **Docker** and **Docker Compose**
- **Git**
- **VSCode** (recommended editor)

## 🛠️ Quick Setup

### 1. Clone and Setup Environment

```bash
# Clone the repository
git clone https://github.com/amirhosseindz/ai-task-manager.git
cd ai-task-manager

# Run the setup script to create environment files
./setup-env.sh
```

### 2. Configure Your Credentials

Edit the generated `.env` files with your actual credentials:

```bash
# Main infrastructure secrets
vim .env

# Add your OpenAI API key
vim ai-agent/.env
```

### 3. Start Infrastructure Services

```bash
# Start NATS, PostgreSQL, and Redis
docker-compose up -d

# Verify services are running
curl http://localhost:8222/varz  # NATS monitoring
docker-compose exec postgres psql -U dev_user -d task_manager -c "SELECT version();"
```

### 4. Install Dependencies

```bash
# Install Symfony dependencies for each service
cd services/user-service && composer install && cd ../..
cd services/task-service && composer install && cd ../..
cd services/notification-service && composer install && cd ../..
cd services/api-gateway && composer install && cd ../..

# Install Python dependencies for AI agent
cd ai-agent
source .venv/bin/activate
uv pip install -r requirements.txt
cd ..
```

### 5. Run Database Migrations

```bash
# Create and run migrations for each service that uses the database
cd services/user-service
php bin/console doctrine:database:create --if-not-exists
php bin/console make:migration
php bin/console doctrine:migrations:migrate -n
cd ../..

cd services/task-service  
php bin/console doctrine:database:create --if-not-exists
php bin/console make:migration
php bin/console doctrine:migrations:migrate -n
cd ../..
```

## 🏃‍♂️ Running the Application

### Start All Services

```bash
# Terminal 1: User Service
cd services/user-service
symfony server:start --port=8001

# Terminal 2: Task Service  
cd services/task-service
symfony server:start --port=8002

# Terminal 3: Notification Service
cd services/notification-service
symfony server:start --port=8003

# Terminal 4: API Gateway
cd services/api-gateway
symfony server:start --port=8000

# Terminal 5: AI Agent
cd ai-agent
source .venv/bin/activate
uvicorn main:app --host 0.0.0.0 --port 8004 --reload
```

### Verify Everything is Running

```bash
curl http://localhost:8000/health  # API Gateway
curl http://localhost:8001/health  # User Service
curl http://localhost:8002/health  # Task Service
curl http://localhost:8003/health  # Notification Service
curl http://localhost:8004/health  # AI Agent
```

## 📁 Project Structure

```
ai-task-manager/
├── services/
│   ├── user-service/           # User authentication & profiles
│   ├── task-service/           # Task CRUD operations
│   ├── notification-service/   # Email/SMS notifications
│   └── api-gateway/            # Request routing & CORS
├── ai-agent/                   # Python AI agent with MCP
├── docker-compose.yml          # Infrastructure services
├── .env                        # Infrastructure secrets
├── .env.example                # Environment template
├── setup-env.sh                # Environment setup script
├── .gitignore                  # Git ignore rules
└── docs/                       # Documentation
```

## 🔧 Development Workflow

### Adding a New Feature

1. **Create the API endpoint** in the appropriate Symfony service
2. **Publish events** via NATS when data changes
3. **Subscribe to events** in other services that need to react
4. **Update the AI agent** to handle new functionality
5. **Test the integration** across services

### Example: Adding Task Priority Feature

```php
// In Task Service
$this->eventBus->publish('task.priority.changed', [
    'taskId' => $task->getId(),
    'newPriority' => $priority,
    'userId' => $task->getUserId()
]);
```

```python
# In AI Agent
@nats_subscriber('task.priority.changed')
async def analyze_priority_change(event_data):
    # AI analyzes the priority change and suggests optimizations
    suggestions = await generate_priority_suggestions(event_data)
    await publish_ai_insights(suggestions)
```

## 🔒 Security Best Practices

- ✅ **Environment files are gitignored** - no secrets in version control
- ✅ **Service isolation** - each service only gets secrets it needs
- ✅ **Least privilege principle** - minimal access per service
- ✅ **Separate development/production configs**

## 🧪 Testing

```bash
# Run tests for individual services
cd services/user-service && php bin/phpunit
cd services/task-service && php bin/phpunit

# Run AI agent tests
cd ai-agent && python -m pytest
```

## 📚 API Documentation

Once the services are running, API documentation will be available at:
- API Gateway: http://localhost:8000/api/doc
- User Service: http://localhost:8001/api/doc  
- Task Service: http://localhost:8002/api/doc

## 🤖 AI Agent Features

The AI agent integrates with various MCP servers to provide:
- **File System Access** - Read/write project files
- **Calendar Integration** - Schedule tasks and deadlines
- **Database Queries** - Generate insights and analytics
- **Natural Language Processing** - Understand user requests

## 🐛 Troubleshooting

### Common Issues

**NATS Connection Failed:**
```bash
docker-compose logs nats
docker-compose restart nats
```

**Database Connection Issues:**
```bash
docker-compose logs postgres
# Check your DATABASE_URL in service .env files
```

**Services Not Starting:**
```bash
# Check if ports are in use
lsof -i :8000  # API Gateway
lsof -i :8001  # User Service
# Kill processes if necessary
```

**AI Agent Import Errors:**
```bash
cd ai-agent
source .venv/bin/activate
pip list  # Verify dependencies
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with [Symfony 7](https://symfony.com/)
- Powered by [NATS](https://nats.io/) for messaging
- AI integration via [OpenAI API](https://openai.com/api/)
- MCP servers for extended functionality