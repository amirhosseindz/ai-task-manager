from pathlib import Path
from typing import Optional
from pydantic_settings import BaseSettings

# Get the ai-agent directory path
BASE_DIR = Path(__file__).resolve().parent.parent

class Settings(BaseSettings):
    """Application settings loaded from environment variables"""
    
    # App settings
    app_name: str = "AI Task Manager Agent"
    app_version: str = "1.0.0"
    debug: bool = True
    
    # Server settings
    host: str = "0.0.0.0"
    port: int = 8004
    
    # OpenAI settings
    openai_api_key: Optional[str] = None
    
    # NATS settings
    nats_url: str = "nats://localhost:4222"
    
    # Symfony integration
    symfony_api_url: str = "http://localhost:8002/api"
    
    # Database settings (for analytics)
    database_url: Optional[str] = None
    
    # Redis settings
    redis_url: Optional[str] = None
    
    class Config:
        env_file = BASE_DIR / ".env"
        case_sensitive = False


# Global settings instance
settings = Settings()
