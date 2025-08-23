import json
from typing import Dict, Any, Callable, Optional
import nats
from nats.errors import TimeoutError, NoServersError
from app.config import settings
from app.utils.logger import app_logger
from nats.aio.client import Client as NATS


class NATSService:
    """NATS messaging service for communication with Symfony"""
    
    def __init__(self):
        self.nc: Optional[NATS] = None
        self.subscriptions: Dict[str, Any] = {}
        self.logger = app_logger
    
    async def connect(self) -> bool:
        """Connect to NATS server"""
        try:
            self.nc = await nats.connect(settings.nats_url)
            self.logger.info(f"Connected to NATS at {settings.nats_url}")
            return True
        except (TimeoutError, NoServersError) as e:
            self.logger.error(f"Failed to connect to NATS: {e}")
            return False
    
    async def disconnect(self):
        """Disconnect from NATS server"""
        if self.nc:
            await self.nc.close()
            self.logger.info("Disconnected from NATS")
    
    async def publish(self, subject: str, data: Dict[str, Any]) -> bool:
        """Publish a message to NATS"""
        if not self.nc:
            self.logger.error("Not connected to NATS")
            return False
        
        try:
            message = json.dumps(data).encode()
            await self.nc.publish(subject, message)
            self.logger.info(f"Published to {subject}: {data}")
            return True
        except Exception as e:
            self.logger.error(f"Failed to publish to {subject}: {e}")
            return False
    
    async def subscribe(self, subject: str, callback: Callable) -> bool:
        """Subscribe to a NATS subject"""
        if not self.nc:
            self.logger.error("Not connected to NATS")
            return False
        
        try:
            async def message_handler(msg):
                try:
                    data = json.loads(msg.data.decode())
                    self.logger.info(f"Received from {subject}: {data}")
                    await callback(subject, data)
                except Exception as e:
                    self.logger.error(f"Error processing message from {subject}: {e}")
            
            sub = await self.nc.subscribe(subject, cb=message_handler)
            self.subscriptions[subject] = sub
            self.logger.info(f"Subscribed to {subject}")
            return True
        except Exception as e:
            self.logger.error(f"Failed to subscribe to {subject}: {e}")
            return False
    
    async def request(self, subject: str, data: Dict[str, Any], timeout: float = 5.0) -> Optional[Dict[str, Any]]:
        """Send a request and wait for response"""
        if not self.nc:
            self.logger.error("Not connected to NATS")
            return None
        
        try:
            message = json.dumps(data).encode()
            response = await self.nc.request(subject, message, timeout=timeout)
            response_data = json.loads(response.data.decode())
            self.logger.info(f"Request to {subject} returned: {response_data}")
            return response_data
        except TimeoutError:
            self.logger.error(f"Request to {subject} timed out")
            return None
        except Exception as e:
            self.logger.error(f"Request to {subject} failed: {e}")
            return None


# Global NATS service instance
nats_service = NATSService()
