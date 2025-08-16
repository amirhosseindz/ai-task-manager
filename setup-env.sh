#!/bin/bash

echo "Setting up service-specific environment files..."

# Get credentials once
read -p "Enter database username [dev_user]: " db_user
db_user=${db_user:-dev_user}

read -s -p "Enter database password: " db_password
echo
read -s -p "Enter Redis password: " redis_password
echo

# Create main .env for Docker
cat > .env << EOF_MAIN
DB_NAME=task_manager
DB_USER=$db_user
DB_PASSWORD=$db_password
REDIS_PASSWORD=$redis_password
EOF_MAIN

# Create service-specific .env files with only needed secrets
cat > services/user-service/.env << EOF_USER
APP_ENV=dev
DATABASE_URL=postgresql://$db_user:$db_password@localhost:5432/task_manager
NATS_URL=nats://localhost:4222
EOF_USER

cat > services/task-service/.env << EOF_TASK
APP_ENV=dev
DATABASE_URL=postgresql://$db_user:$db_password@localhost:5432/task_manager
NATS_URL=nats://localhost:4222
USER_SERVICE_URL=http://localhost:8001
EOF_TASK

cat > services/notification-service/.env << EOF_NOTIFICATION
APP_ENV=dev
NATS_URL=nats://localhost:4222
REDIS_URL=redis://:$redis_password@localhost:6379
EOF_NOTIFICATION

cat > services/api-gateway/.env << EOF_GATEWAY
APP_ENV=dev
USER_SERVICE_URL=http://localhost:8001
TASK_SERVICE_URL=http://localhost:8002
NOTIFICATION_SERVICE_URL=http://localhost:8003
NATS_URL=nats://localhost:4222
EOF_GATEWAY

cat > ai-agent/.env << EOF_AI
OPENAI_API_KEY=your-openai-api-key-here
NATS_URL=nats://localhost:4222
TASK_SERVICE_URL=http://localhost:8002
EOF_AI

echo "✅ Environment files created with least-privilege access!"
echo "⚠️  Don't forget to add your OpenAI API key to ai-agent/.env"
