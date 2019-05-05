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

    public function __construct(string $name, string $content, int $expire = 0)
    {
        $this->name = $name;
        $this->content = $content;
        $this->expire = $expire;
    }
}
