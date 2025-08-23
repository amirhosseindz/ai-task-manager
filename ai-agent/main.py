import sys
from pathlib import Path

# Add the ai-agent directory to Python path
sys.path.insert(0, str(Path(__file__).parent))

import uvicorn
from app.main import app
from app.config import settings
from app.utils.logger import app_logger


if __name__ == "__main__":
    app_logger.info(f"Starting AI Agent on {settings.host}:{settings.port}")
    
    uvicorn.run(
        "app.main:app",  # Use string reference for hot reload
        host=settings.host,
        port=settings.port,
        reload=settings.debug,
        log_level="info"
    )
