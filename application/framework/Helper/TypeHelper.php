<?php

namespace App\Helper;


class TypeHelper
{
    /**
     * Zwraca nazwę typu zmiennej
     *
     * @param mixed $var
     *
     * @return string
     */
    public static function get($var): string
    {
        $type = gettype($var);
        if ($type === 'integer') {
            $type = 'int';
        } elseif ($type === 'double') {
            $type = 'float';
        } elseif ($type === 'boolean') {
            $type = 'bool';
        }

        return $type;
    }

    /**
     * Rzutuje zmienną na podany typ
     *
     * @param mixed  $var
     * @param string $type
     *
     * @return mixed
     */
    public static function cast($var, string $type)
    {
        if ($type === 'int' || $type === 'integer') {
            return (int)$var;
        } elseif ($type === 'float' || $type === 'double') {
            return (float)$var;
        } elseif ($type === 'bool' || $type === 'boolean') {
            return (bool)$var;
        } elseif ($type === 'array') {
            return (array)$var;
        } elseif ($type === 'string') {
            return (string)filter_var($var, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }

        return $var;
    }
}
