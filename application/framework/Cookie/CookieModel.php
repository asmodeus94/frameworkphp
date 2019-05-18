<?php

namespace App\Cookie;


class CookieModel
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $content;

    /**
     * @var int|null
     */
    public $expire = 0;

    /**
     * @var string|null
     */
    public $path = null;

    /**
     * @var string|null
     */
    public $domain = null;

    /**
     * @var bool|null
     */
    public $secure = null;

    /**
     * @var bool|null
     */
    public $httpOnly = null;
}
