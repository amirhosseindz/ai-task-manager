# 🤖 AI Task Manager

A smart task management app where you can create and manage tasks using natural language. Just tell the AI what you want to do, and it handles the rest!

## ✨ What it does

- **Talk to AI**: "Hey AI, remind me to call mom tomorrow at 3 PM"
- **Smart scheduling**: AI figures out priorities and deadlines
- **Get notifications**: Email/SMS when tasks are due
- **Natural language**: No complex forms, just chat with the AI

## 🚀 Quick Start (5 minutes)

### Prerequisites
- macOS or Linux
- Docker installed
- Git installed

### Installation

```bash
# 1. Clone and enter the project
git clone https://github.com/amirhosseindz/ai-task-manager.git
cd ai-task-manager

# 2. Quick setup (creates all config files)
chmod +x setup.sh
./setup.sh

# 3. Add your OpenAI API key
echo "OPENAI_API_KEY=your_openai_key_here" >> .env

# 4. Start everything
docker-compose up -d  # Database
cd symfony-app && symfony server:start -d --port=8000  # Main app
cd ../ai-agent && python -m uvicorn main:app --port=8004  # AI agent
```

### Test it works

```bash
# Create a user
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": "John", "email": "john@example.com"}'

# Talk to AI (replace USER_ID with the ID from above)
curl -X POST http://localhost:8004/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Add a task to buy groceries tomorrow", "user_id": 1}'
```

## 🎮 How to Play With It

### 1. Web Interface (Coming Soon!)
Visit `http://localhost:8000` for a simple web UI.

### 2. Chat with AI via API

```bash
# Natural language task creation
curl -X POST http://localhost:8004/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Schedule a meeting with the team next Friday at 2 PM",
    "user_id": 1
  }'

# Ask for task insights
curl -X POST http://localhost:8004/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "What should I work on today?",
    "user_id": 1
  }'
```

### 3. Direct API Calls

```bash
# Get all tasks
curl http://localhost:8000/api/tasks

# Create task manually
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Learn AI",
    "description": "Study machine learning basics",
    "due_date": "2024-12-31",
    "priority": "high"
  }'
```

## 🛠️ Development Mode

Want to hack on it? Here's how to run in development:

```bash
# Install dependencies
cd symfony-app && composer install
cd ../ai-agent && pip install -r requirements.txt

# Run with hot reload
symfony server:start --port=8000  # Symfony with auto-reload
uvicorn main:app --reload --port=8004  # AI agent with hot reload
```

## 📱 API Endpoints

### Main App (Port 8000)
- `GET /api/tasks` - List all tasks
- `POST /api/tasks` - Create new task
- `GET /api/users` - List users
- `POST /api/users` - Create user

### AI Agent (Port 8004)
- `POST /chat` - Chat with AI
- `GET /health` - Health check

## 🔧 Configuration

Edit `.env` files to customize:

```bash
# Main app config
vim symfony-app/.env

# AI agent config (add your OpenAI key here!)
vim ai-agent/.env
```

## 🐛 Troubleshooting

**Nothing works?**
```bash
# Check if services are running
curl http://localhost:8000/health
curl http://localhost:8004/health

# Restart everything
docker-compose restart
symfony server:stop && symfony server:start -d
```

**AI not responding?**
- Make sure you added your OpenAI API key to `ai-agent/.env`
- Check AI agent logs: `tail -f ai-agent/logs/app.log`

**Database issues?**
```bash
# Reset database
cd symfony-app
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 🎯 Cool Things to Try

1. **Smart Scheduling**: "Plan my week with 3 important tasks"
2. **Context Awareness**: "Move my Monday meeting to Tuesday"
3. **Priority Management**: "What's the most urgent thing I should do?"
4. **Natural Deadlines**: "Remind me to submit the report before the weekend"

## 🏗️ Architecture

Simple but powerful:
- **Symfony 7** - Main web application 
- **Python FastAPI** - AI agent with OpenAI integration
- **PostgreSQL** - Data storage
- **Docker** - Easy infrastructure

## 🤝 Contributing

This is a weekend project, but PRs welcome! 

1. Fork it
2. Create feature branch
3. Make it awesome  
4. Submit PR

## 📄 License

MIT - Do whatever you want with it!

---

**Questions?** Open an issue or just hack away! 🚀