from pydantic import BaseModel, Field
from typing import Optional, Dict, Any


class HealthResponse(BaseModel):
    """Health check response model"""
    status: str = Field(..., description="Service status")
    service: str = Field(..., description="Service name")
    version: str = Field(..., description="Service version")
    openai_configured: bool = Field(..., description="Whether OpenAI is properly configured")


class ChatRequest(BaseModel):
    """Chat request model for AI interactions"""
    message: str = Field(..., min_length=1, max_length=1000, description="User message")
    user_id: int = Field(..., gt=0, description="User ID")
    context: Optional[Dict[str, Any]] = Field(None, description="Additional context")


class ChatResponse(BaseModel):
    """Chat response model from AI"""
    response: str = Field(..., description="AI response")
    user_id: int = Field(..., description="User ID")
    action_taken: Optional[str] = Field(None, description="Action performed by AI")
    metadata: Optional[Dict[str, Any]] = Field(None, description="Additional response metadata")


class ErrorResponse(BaseModel):
    """Error response model"""
    error: str = Field(..., description="Error message")
    detail: Optional[str] = Field(None, description="Detailed error information")
    code: Optional[str] = Field(None, description="Error code")
