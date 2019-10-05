<?php

namespace App\Security;


use App\Redirect;
use App\Request;
use App\Session;

class Csrf
{
    const TOKEN_NAME = 'token';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Request
     */
    private $request;

    /**
     * Csrf constructor.
     *
     * @param Session $session
     * @param Request $request
     */
    public function __construct(Session $session, Request $request)
    {
        $this->session = $session;
        $this->request = $request;
    }

    /**
     * Sprawdza token CSRF, a w przypadku jego braku lub niezgodności z tym zapisanym w sesji przekierowuje na stronę
     * główną
     */
    public function checkToken(): void
    {
        $tokenFromSession = $this->session->get(self::TOKEN_NAME);
        if (null !== $tokenFromSession && $this->request->getParameter(self::TOKEN_NAME) === $tokenFromSession) {
            return;
        }

        (new Redirect('/'))->make();
    }

    /**
     * Zapisuje token w sesji
     *
     * @param string $token
     */
    private function saveInSession(string $token): void
    {
        $this->session->set(self::TOKEN_NAME, $token);
    }

    /**
     * Pobiera token, a w przypadku jego braku tworzy go i zapisuje w sesji
     *
     * @return string
     */
    public function getToken(): string
    {
        if (null !== ($token = $this->session->get(self::TOKEN_NAME))) {
            return $token;
        }

        $token = CryptographyHelper::hash(CryptographyHelper::getRandomBytes());
        $this->saveInSession($token);

        return $token;
    }
}
