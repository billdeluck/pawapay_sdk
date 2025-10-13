<?php

namespace Myzuwa\PawaPay\Support;

/**
 * Input helper functions for handling request parameters
 */
class InputHelper
{
    /**
     * Get a value from $_POST, sanitized
     */
    public static function post($key, $default = null)
    {
        return filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING) ?: $default;
    }

    /**
     * Get a value from $_GET, sanitized
     */
    public static function get($key, $default = null)
    {
        return filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING) ?: $default;
    }
}