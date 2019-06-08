<?php

namespace App\Helper;


use App\Route\Route;

class RouteHelper
{
    /**
     * Tworzy ścieżkę na podstawie nazwy routingu, parametów lub dodatkowych parametrów (które będą rozdzielone &)
     *
     * @param string|null $routeName Nazwa routingu
     * @param array|null  $params
     * @param array|null  $query
     *
     * @return string
     */
    public static function path(?string $routeName, ?array $params = [], ?array $query = []): string
    {
        if ($routeName === null || $params === null || $query === null) {
            return '';
        }

        return ($path = Route::getInstance()->makePath($routeName, $params, $query)) !== null ? $path : '';
    }
}
