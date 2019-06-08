<?php

namespace App\Helper;


class ServerHelper
{
    public static function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
