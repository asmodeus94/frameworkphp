<?php

namespace App\Security;


use App\Redirect;
use App\Request;
use App\Session;

class Csrf
{
    private const TOKEN_NAME = 'token';

    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Sprawdza token CSRF, a w przypadku jego braku lub niezgoności z tym zapisanym w sesji przekierowuje na stronę
     * główną
     */
    public function checkToken(): void
    {
        if (Request::getInstance()->getParameter(self::TOKEN_NAME) === $this->session->get(self::TOKEN_NAME)) {
            return;
        }

        (new Redirect('/'))->make();
    }
}
