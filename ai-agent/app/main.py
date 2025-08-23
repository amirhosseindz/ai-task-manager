from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.config import settings
from app.api.routes import health
from app.utils.logger import app_logger


def create_app() -> FastAPI:
    """Create and configure FastAPI application"""
    
    app = FastAPI(
        title=settings.app_name,
        description="Intelligent task management through natural language",
        version=settings.app_version,
        debug=settings.debug
    )
    
    # Configure CORS
    app.add_middleware(
        CORSMiddleware,
        allow_origins=[
            settings.symfony_api_url,
            "http://localhost:8000",
            "http://127.0.0.1:8000",
            "http://localhost:3000",  # Future frontend
        ],
        allow_credentials=True,
        allow_methods=["*"],
        allow_headers=["*"],
    )
    
    # Include routers
    app.include_router(health.router, tags=["health"])
    
    # Startup event
    @app.on_event("startup")
    async def startup_event():
        app_logger.info(f"Starting {settings.app_name} v{settings.app_version}")
        app_logger.info(f"OpenAI configured: {settings.openai_api_key is not None}")
        app_logger.info(f"Symfony API URL: {settings.symfony_api_url}")
    
    # Shutdown event  
    @app.on_event("shutdown")
    async def shutdown_event():
        app_logger.info(f"Shutting down {settings.app_name}")
    
    return app


# Create app instance
app = create_app()
