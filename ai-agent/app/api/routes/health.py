from fastapi import APIRouter
from app.models.schemas import HealthResponse
from app.config import settings
from app.utils.logger import app_logger

router = APIRouter()


@router.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint to verify AI agent is running"""
    
    # Check if OpenAI is configured
    openai_configured = (
        settings.openai_api_key is not None 
        and settings.openai_api_key != "your-openai-api-key-here"
        and len(settings.openai_api_key) > 10
    )
    
    app_logger.info("Health check requested")
    
    return HealthResponse(
        status="healthy",
        service=settings.app_name,
        version=settings.app_version,
        openai_configured=openai_configured
    )


@router.get("/")
async def root():
    """Root endpoint with API information"""
    return {
        "message": settings.app_name,
        "version": settings.app_version,
        "docs": "/docs",
        "health": "/health",
        "endpoints": {
            "health": "/health",
            "chat": "/chat"  # Will be added in next step
        }
    }
