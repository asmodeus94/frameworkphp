<?php

namespace App\Helper;


use App\Route;

class Url
{
    /**
     * @param string $routeName
     * @param array  $url
     *
     * @return string
     */
    public static function make(string $routeName, string $url): string
    {
        return Route::getInstance()->validate($routeName, $url) ? $url : '';
    }
}
