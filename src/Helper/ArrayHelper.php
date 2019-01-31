<?php

declare(strict_types=1);

namespace Mapado\RestClientSdk\Helper;

/**
 * Some array helpers.
 * Greatly inspired by Laravel's helper: https://github.com/rappasoft/laravel-helpers
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ArrayHelper
{
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  mixed   $default
     *
     * @return mixed
     */
    public static function arrayGet(
        array $array,
        ?string $key,
        $default = null
    ) {
        if (null === $key) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return self::value($default);
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     */
    public static function arrayHas(array $array, ?string $key): bool
    {
        if (empty($array) || null === $key) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     */
    public static function arrayDot(array $array, string $prepend = ''): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge(
                    $results,
                    static::arrayDot($value, $prepend . $key . '.')
                );
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    public static function arrayDiffAssocRecursive(
        array $array1,
        array $array2
    ): array {
        return array_diff_assoc(
            static::arrayDot($array1),
            static::arrayDot($array2)
        );
    }

    public static function arraySame(array $array1, array $array2): bool
    {
        return empty(static::arrayDiffAssocRecursive($array1, $array2));
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     *
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}
