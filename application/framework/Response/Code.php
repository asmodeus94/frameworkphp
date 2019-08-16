<?php

namespace App\Response;


use App\Helper\Traits\ConstantExportTrait;

class Code
{
    use ConstantExportTrait;

    const OK = 200;
    const PARTIAL_CONTENT = 206;

    const MOVED_PERMANENTLY = 301;
    const MOVED_TEMPORARILY = 302;

    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const RANGE_NOT_SATISFIABLE = 416;

    const INTERNAL_SERVER_ERROR = 500;
}
