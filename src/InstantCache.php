<?php

/*
 * Copyright (c) 2022 5ever.
 */

namespace src;

class InstantCache
{
    /**
     * @param string $name
     * @return mixed
     */
    public static function get(string $name): mixed
    {
        return $GLOBALS['instantcache'][$name] ?? null;
    }

    /**
     * @param string $name
     * @param mixed $data
     * @return mixed
     */
    public static function set(
        string $name,
        mixed $data
    ): mixed {
        $GLOBALS['instantcache'][$name] = $data;
        return $data;
    }

    /**
     * @param string $name
     */
    public static function delete(
        string $name
    ): void {
        if (self::isset($name)) {
            unset($GLOBALS['instantcache'][$name]);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isset(
        string $name
    ): bool {
        return isset($GLOBALS['instantcache'][$name]);
    }
}
