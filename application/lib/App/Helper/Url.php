<?php

namespace App\Helper;


use App\Route;

class Url
{
    /**
     * Tworzy adres w przypadku gdy jest on zgodny z podanym routingiem
     *
     * @param string $routeName Nazwa routingu
     * @param string $url       Adres
     *
     * @return string
     */
    public static function make(string $routeName, string $url): string
    {
        return Route::getInstance()->validate($routeName, $url) ? $url : '';
    }

    /**
     * Łączy przekazane argumenty przy pomocy znaku "/"
     *
     * @param array $args
     *
     * @return string
     */
    public static function implode(array $args): string
    {
        if (empty($args)) {
            return '';
        }

        $imploded = '';
        foreach ($args as $key => $arg) {
            if (is_int($key)) {
                continue;
            }

            $imploded .= $arg !== true ? $key . '/' . $arg . '/' : $key . '/';
        }

        return rtrim($imploded, '/');
    }
}
