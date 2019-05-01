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
     * @var int
     */
    public $expire = 0;

    /**
     * @var string
     */
    public $path = '/';

    /**
     * @var string
     */
    public $domain = '';

    /**
     * @var bool
     */
    public $secure = true;

    /**
     * @var bool
     */
    public $httpOnly = true;

    public function __construct(string $name, string $content, int $expire)
    {
        $this->name = $name;
        $this->content = $content;
        $this->expire = $expire;
    }
}
