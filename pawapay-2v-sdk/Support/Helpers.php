<?php

namespace Myzuwa\PawaPay\Support;

if (!function_exists('Myzuwa\PawaPay\Support\env')) {
/**
 * Get an environment variable value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env($key, $default = null)
{
    $value = $_ENV[$key] ?? null;
        
    if ($value === null) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}
}

if (!function_exists('Myzuwa\PawaPay\Support\storage_path')) {
    /**
     * Get the path to a storage directory.
     *
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        $storagePath = rtrim(env('STORAGE_PATH', __DIR__ . '/../../storage'), '/');
        return $path ? $storagePath . '/' . ltrim($path, '/') : $storagePath;
    }
}

if (!function_exists('Myzuwa\PawaPay\Support\base_url')) {
    /**
     * Get the base URL for the application.
     *
     * @param string $path
     * @return string
     */
    function base_url($path = '')
    {
        $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
        return $path ? $baseUrl . '/' . ltrim($path, '/') : $baseUrl;
    }
}/**
 * Get the base URL for the application.
 *
 * @param string $path
 * @return string
 */
function base_url($path = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = rtrim($protocol . $host, '/');
    
    return $path ? $baseUrl . '/' . ltrim($path, '/') : $baseUrl;
}

if (!function_exists('Myzuwa\PawaPay\Support\inputGet')) {
    /**
     * Get a value from $_GET, sanitized.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function inputGet($key, $default = null)
    {
        return filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING) ?? $default;
    }
}

if (!function_exists('Myzuwa\PawaPay\Support\inputPost')) {
    /**
     * Get a value from $_POST, sanitized.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function inputPost($key, $default = null)
    {
        return filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING) ?? $default;
    }
}

/**
 * Get the storage path for the application.
 *
 * @param string $path
 * @return string
 */
function storage_path($path = '')
{
    $basePath = dirname(dirname(__DIR__));
    $storagePath = $basePath . DIRECTORY_SEPARATOR . 'storage';
    
    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0777, true);
    }
    
    return $path ? $storagePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $storagePath;
}