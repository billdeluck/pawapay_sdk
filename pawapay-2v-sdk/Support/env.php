<?php

namespace Myzuwa\PawaPay\Support;

use Dotenv\Dotenv;

/**
 * Environment Helper Function
 *
 * Gets environment variables with fallback to default values
 *
 * @param string $key The environment variable key
 * @param mixed $default Default value if key not found
 * @return mixed The environment variable value or default
 */
function env($key, $default = null)
{
    // Since bootstrap.php now handles loading, we can directly check superglobals
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

    if ($value !== null) {
        return $value;
    }

    return $default;
}


/**
 * Get base URL for the application
 *
 * @param string $path Optional path to append
 * @return string The base URL with optional path
 */
function base_url($path = '')
{
    $baseUrl = env('APP_URL', 'http://localhost');

    if (!empty($path)) {
        $baseUrl = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    return $baseUrl;
}

/**
 * Get storage path for the application
 *
 * @param string $path Optional path to append
 * @return string The storage path with optional path
 */
function storage_path($path = '')
{
    $storagePath = __DIR__ . '/../../storage';

    if (!empty($path)) {
        $storagePath .= '/' . ltrim($path, '/');
    }

    return $storagePath;
}
