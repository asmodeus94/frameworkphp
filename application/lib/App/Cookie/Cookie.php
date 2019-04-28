<?php

namespace App\Cookie;


use Constant;

class Cookie extends CookieModel
{
    public function __construct(string $name, string $content)
    {
        $this->name = $name;
        $this->content = $content;
    }

    /**
     *
     */
    public function remove(): void
    {
        setcookie($this->name, '', 1);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * Tworzy nowe ciastko
     *
     * @param CookieModel $cookieModel
     *
     * @return bool
     */
    public static function addCookie(CookieModel $cookieModel): bool
    {
        return setcookie(
            $cookieModel->name,
            $cookieModel->content,
            $cookieModel->expire,
            $cookieModel->path,
            $cookieModel->domain,
            $cookieModel->secure,
            $cookieModel->httpOnly
        );
    }
}
