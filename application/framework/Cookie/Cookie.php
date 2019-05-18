<?php

namespace App\Cookie;

class Cookie extends CookieModel
{
    public function __construct(string $name, string $content)
    {
        $this->name = $name;
        $this->content = $content;
        $this->expire = null;
    }

    /**
     * Usuwa ciastko
     *
     * @param string $path
     * @param string $domain
     */
    public function remove(string $path = '/', string $domain = ''): void
    {
        setcookie($this->name, '', 1, $path, $domain);
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
            $cookieModel->path ?? '/',
            $cookieModel->domain ?? '',
            $cookieModel->secure ?? true,
            $cookieModel->httpOnly ?? true
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
