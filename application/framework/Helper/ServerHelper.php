<?php

namespace App\Helper;


class ServerHelper
{
    public static function isCLI(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
