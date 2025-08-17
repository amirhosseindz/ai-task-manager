<?php

namespace App\Task\Service;

use Psr\Log\LoggerInterface;

class NatsPublisher
{
    private $connection;
    private LoggerInterface $logger;
    private string $natsUrl;

    public function __construct(LoggerInterface $logger, string $natsUrl = 'nats://localhost:4222')
    {
        $this->logger = $logger;
        $this->natsUrl = $natsUrl;
    }

    public function connect(): void
    {
        if ($this->connection) {
            return;
        }

        try {
            // Simple TCP connection to NATS
            $parsedUrl = parse_url($this->natsUrl);
            $host = $parsedUrl['host'] ?? 'localhost';
            $port = $parsedUrl['port'] ?? 4222;

            $this->connection = fsockopen($host, $port, $errno, $errstr, 30);
            
            if (!$this->connection) {
                throw new \Exception("Failed to connect to NATS: $errstr ($errno)");
            }

            // Send CONNECT message
            $connectMsg = "CONNECT {\"verbose\":false,\"pedantic\":false,\"name\":\"symfony-task-service\"}\r\n";
            fwrite($this->connection, $connectMsg);
            
            $this->logger->info('Connected to NATS server', ['host' => $host, 'port' => $port]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to connect to NATS', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function publish(string $subject, array $data): void
    {
        try {
            $this->connect();
            
            $payload = json_encode($data);
            $payloadLength = strlen($payload);
            
            // NATS protocol: PUB <subject> <bytes>\r\n<payload>\r\n
            $pubMsg = "PUB {$subject} {$payloadLength}\r\n{$payload}\r\n";
            
            fwrite($this->connection, $pubMsg);
            
            $this->logger->info('Published message to NATS', [
                'subject' => $subject,
                'payload_size' => $payloadLength
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish to NATS', [
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function publishTaskEvent(string $eventType, array $taskData): void
    {
        $eventData = [
            'event_type' => $eventType,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'service' => 'task-service',
            'data' => $taskData
        ];

        $this->publish("task.{$eventType}", $eventData);
    }

    public function __destruct()
    {
        if ($this->connection) {
            fclose($this->connection);
        }
    }
}
