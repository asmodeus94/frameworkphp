<?php

namespace App\Helper;


use App\Route;

class RouteHelper
{
    /**
     * Tworzy ścieżkę na podstawie nazwy routingu, parametów lub dodatkowych parametrów (które będą rozdzielone &)
     *
     * @param string $routeName Nazwa routingu
     * @param array  $params
     * @param array  $query
     *
     * @return string
     */
    public static function path(string $routeName, array $params = [], array $query = []): string
    {
        return ($path = Route::getInstance()->makePath($routeName, $params, $query)) !== null ? $path : '';
    }
}
