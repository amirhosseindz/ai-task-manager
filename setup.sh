#!/bin/bash

echo "🚀 Setting up AI Task Manager..."
echo

# Check prerequisites
check_prerequisites() {
    echo "Checking prerequisites..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        echo "❌ Docker is required but not installed. Please install Docker first."
        exit 1
    fi
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        echo "❌ Docker Compose is required but not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Check Symfony CLI (optional but recommended)
    if ! command -v symfony &> /dev/null; then
        echo "⚠️  Symfony CLI not found. You can install it from https://symfony.com/download"
        echo "    (You can still use 'php -S' as an alternative)"
    fi
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        echo "❌ PHP is required but not installed. Please install PHP 8.1+ first."
        exit 1
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        echo "❌ Composer is required but not installed. Please install Composer first."
        exit 1
    fi
    
    # Check Python
    if ! command -v python3 &> /dev/null && ! command -v python &> /dev/null; then
        echo "❌ Python is required but not installed. Please install Python 3.8+ first."
        exit 1
    fi
    
    echo "✅ All prerequisites found!"
    echo
}

# Get credentials from user
get_credentials() {
    echo "Database Setup:"
    read -p "Enter database username [dev_user]: " db_user
    db_user=${db_user:-dev_user}
    
    read -s -p "Enter database password [dev_pass]: " db_password
    echo
    db_password=${db_password:-dev_pass}
    
    echo
    echo "Redis Setup:"
    read -s -p "Enter Redis password [redis_pass]: " redis_password
    echo
    redis_password=${redis_password:-redis_pass}
    
    echo
    echo "OpenAI Setup:"
    read -p "Enter your OpenAI API key (or leave blank to set later): " openai_key
    openai_key=${openai_key:-your-openai-api-key-here}
    
    echo
}

# Create environment files
create_env_files() {
    echo "📝 Creating environment files..."
    
    # Create main .env for Docker infrastructure
    cat > .env << EOF_MAIN
# Infrastructure Environment Variables
DB_NAME=task_manager
DB_USER=$db_user
DB_PASSWORD=$db_password
REDIS_PASSWORD=$redis_password

# Ports
POSTGRES_PORT=5432
REDIS_PORT=6379
EOF_MAIN

    # Create Symfony app .env
    cat > symfony-app/.env << EOF_SYMFONY
# Symfony Environment
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=$(openssl rand -hex 16)

# Database
DATABASE_URL=postgresql://$db_user:$db_password@localhost:5432/task_manager?serverVersion=15&charset=utf8

# Redis (for caching and sessions)
REDIS_URL=redis://:$redis_password@localhost:6379/0

# Messenger (for async processing)
MESSENGER_TRANSPORT_DSN=redis://:$redis_password@localhost:6379/1

# Cache
CACHE_DSN=redis://:$redis_password@localhost:6379/2

# Mailer (for notifications)
MAILER_DSN=smtp://localhost:1025

# AI Agent
AI_AGENT_URL=http://localhost:8004

# CORS (for API access)
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
EOF_SYMFONY

    # Create AI agent .env
    cat > ai-agent/.env << EOF_AI
# AI Agent Environment
OPENAI_API_KEY=$openai_key

# Symfony App Integration
SYMFONY_API_URL=http://localhost:8000

# Database (for direct analytics queries)
DATABASE_URL=postgresql://$db_user:$db_password@localhost:5432/task_manager

# Redis (for caching AI responses)
REDIS_URL=redis://:$redis_password@localhost:6379/3
EOF_AI

    echo "✅ Environment files created!"
    echo
}

# Verify project structure exists
verify_project_structure() {
    echo "🔍 Verifying project structure..."
    
    if [ ! -d "symfony-app" ]; then
        echo "❌ symfony-app directory not found. Are you in the right directory?"
        exit 1
    fi
    
    if [ ! -d "ai-agent" ]; then
        echo "❌ ai-agent directory not found. Are you in the right directory?"
        exit 1
    fi
    
    echo "✅ Project structure verified!"
    echo
}

# Install dependencies
install_dependencies() {
    echo "📦 Installing dependencies..."
    
    # Symfony dependencies
    if [ -d "symfony-app" ]; then
        echo "Installing Symfony dependencies..."
        cd symfony-app
        
        # Create composer.json if it doesn't exist
        if [ ! -f "composer.json" ]; then
            composer init --no-interaction --name="ai-task-manager/symfony-app"
            composer require symfony/framework-bundle
            composer require symfony/orm-pack
            composer require symfony/messenger
            composer require symfony/mailer
            composer require symfony/security-bundle
            composer require symfony/serializer-pack
            composer require doctrine/annotations
            composer require --dev symfony/maker-bundle
            composer require --dev phpunit/phpunit
        else
            composer install
        fi
        
        cd ..
    fi
    
    # Python dependencies
    if [ -d "ai-agent" ]; then
        echo "Installing Python dependencies..."
        cd ai-agent
        
        # Create requirements.txt if it doesn't exist
        if [ ! -f "requirements.txt" ]; then
            cat > requirements.txt << EOF_REQUIREMENTS
fastapi==0.104.1
uvicorn==0.24.0
openai==1.3.7
python-dotenv==1.0.0
httpx==0.25.2
psycopg2-binary==2.9.9
sqlalchemy==2.0.23
pydantic==2.5.0
pytest==7.4.3
EOF_REQUIREMENTS
        fi
        
        # Create virtual environment
        if [ ! -d "venv" ]; then
            python3 -m venv venv
        fi
        
        # Install dependencies
        source venv/bin/activate
        pip install -r requirements.txt
        deactivate
        
        cd ..
    fi
    
    echo "✅ Dependencies installed!"
    echo
}

# Remove the create_basic_files function entirely since files already exist

# Main setup flow
main() {
    check_prerequisites
    verify_project_structure
    get_credentials
    create_env_files
    install_dependencies
    
    echo "🎉 Setup complete!"
    echo
    echo "Next steps:"
    echo "1. Start the infrastructure:"
    echo "   docker-compose up -d"
    echo
    echo "2. Start the Symfony app:"
    echo "   cd symfony-app"
    echo "   symfony server:start --port=8000"
    echo "   (or: php -S localhost:8000 -t public/)"
    echo
    echo "3. Start the AI agent:"
    echo "   cd ai-agent"
    echo "   source venv/bin/activate"
    echo "   python main.py"
    echo
    echo "4. Test it works:"
    echo "   curl http://localhost:8000/health"
    echo "   curl http://localhost:8004/health"
    echo
    
    if [ "$openai_key" = "your-openai-api-key-here" ]; then
        echo "⚠️  Don't forget to add your OpenAI API key to ai-agent/.env"
        echo
    fi
    
    echo "🚀 Happy coding!"
}

# Run the setup
main