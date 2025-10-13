<?php

namespace Myzuwa\PawaPay\Support;

/**
 * Configuration Helper
 * 
 * Provides environment and configuration management for the SDK.
 */
class Config
{
    /** @var array */
    private static $config = [];

    /** @var array */
    private static $env = [];

    /**
     * Load configuration from file
     *
     * @param string $path Path to config file
     * @return array
     */
    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Configuration file not found: {$path}");
        }

        self::$config = require $path;
        return self::$config;
    }

    /**
     * Load environment variables from .env file
     *
     * @param string $path Path to .env file
     * @return void
     */
    public static function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Environment file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = array_pad(explode('=', $line, 2), 2, null);
            $name = trim($name);
            $value = trim($value);

            if (!empty($name)) {
                self::$env[$name] = $value;
            }
        }
    }

    /**
     * Get environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env(string $key, $default = null)
    {
        return self::$env[$key] ?? $default;
    }

    /**
     * Get configuration value
     *
     * @param string $key Dot notation key (e.g., 'api.token')
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $config = self::$config;

        foreach ($segments as $segment) {
            if (!isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Set configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $config = &self::$config;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $config[$segment] = $value;
                break;
            }

            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }

            $config = &$config[$segment];
        }
    }

    /**
     * Get storage path
     *
     * @param string $path
     * @return string
     */
    public static function storagePath(string $path = ''): string
    {
        $basePath = rtrim(self::get('paths.storage', dirname(__DIR__) . '/storage'), '/');
        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Initialize configuration
     *
     * @param string $configPath Path to config file
     * @param string|null $envPath Optional path to .env file
     * @return void
     */
    public static function initialize(string $configPath, ?string $envPath = null): void
    {
        if ($envPath) {
            self::loadEnv($envPath);
        }
        
        self::load($configPath);

        // Ensure storage directory exists
        $storagePath = self::storagePath();
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Ensure logs directory exists
        $logsPath = self::storagePath('logs');
        if (!is_dir($logsPath)) {
            mkdir($logsPath, 0755, true);
        }
    }
}