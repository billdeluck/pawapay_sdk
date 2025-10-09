<?php

namespace Myzuwa\PawaPay\Support;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;

/**
 * Logger Service
 * 
 * Handles all logging operations with proper formatting and rotation
 */
class LogManager implements LoggerInterface
{
    /** @var Logger */
    private $logger;

    /** @var array Log levels mapping */
    private const LEVELS = [
        'debug' => Logger::DEBUG,
        'info' => Logger::INFO,
        'notice' => Logger::NOTICE,
        'warning' => Logger::WARNING,
        'error' => Logger::ERROR,
        'critical' => Logger::CRITICAL,
        'alert' => Logger::ALERT,
        'emergency' => Logger::EMERGENCY,
    ];

    /**
     * Create a new logger instance
     *
     * @param string $channel
     * @param array $config
     */
    public function __construct(string $channel = 'pawapay', array $config = [])
    {
        $this->logger = new Logger($channel);

        $this->configureHandlers($config);
        $this->configureProcessors();
    }

    /**
     * Configure log handlers based on configuration
     *
     * @param array $config
     * @return void
     */
    private function configureHandlers(array $config): void
    {
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s.u",
            true,
            true
        );

        // Daily rotating file handler
        $rotatingHandler = new RotatingFileHandler(
            Config::storagePath('logs/pawapay.log'),
            Config::get('logging.days', 7),
            $this->getLogLevel($config)
        );
        $rotatingHandler->setFormatter($formatter);
        $this->logger->pushHandler($rotatingHandler);

        // Stream handler for immediate output (if enabled)
        if (Config::get('logging.stream_output', false)) {
            $streamHandler = new StreamHandler('php://stdout', $this->getLogLevel($config));
            $streamHandler->setFormatter($formatter);
            $this->logger->pushHandler($streamHandler);
        }
    }

    /**
     * Configure log processors
     *
     * @return void
     */
    private function configureProcessors(): void
    {
        // Add file/line number processor
        $this->logger->pushProcessor(new IntrospectionProcessor());

        // Add web request details processor
        $this->logger->pushProcessor(new WebProcessor());

        // Add memory usage processor
        $this->logger->pushProcessor(new MemoryUsageProcessor());

        // Add transaction context processor
        $this->logger->pushProcessor(function ($record) {
            $record['extra']['transaction_id'] = $this->getTransactionId();
            return $record;
        });
    }

    /**
     * Get current transaction ID from context
     *
     * @return string|null
     */
    private function getTransactionId(): ?string
    {
        // Implement transaction context tracking
        return null;
    }

    /**
     * Get log level from configuration
     *
     * @param array $config
     * @return int
     */
    private function getLogLevel(array $config): int
    {
        $level = strtolower($config['level'] ?? Config::get('logging.level', 'error'));
        return self::LEVELS[$level] ?? Logger::ERROR;
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log notice message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log alert message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Log emergency message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Log arbitrary level message
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}