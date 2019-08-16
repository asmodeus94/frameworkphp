<?php

namespace App;


use App\Response\Code;
use App\Response\Json;
use App\Response\AbstractResponse;
use App\Response\Type;
use App\Response\View;

abstract class AbstractController
{
    /**
     * Przygotowuje obiekt odpowiedzi
     *
     * @param View|array $response
     * @param int        $code
     *
     * @return AbstractResponse
     */
    protected function response($response = [], int $code = Code::OK): AbstractResponse
    {
        if ($response instanceof View) {
            $response->setContentType();
        } else {
            $response = new Json($response);
            $response->encode();
            $response->setContentType(Type::APPLICATION_JSON);
        }

        $response->setCode($code);

        return $response;
    }

    /**
     * Przygotowuje obiekt oodpowiedzi dla błędu
     *
     * @param string $content Treść błędu
     * @param array  $errors  Tablica błędów
     * @param int    $code    Kod błędu
     *
     * @return AbstractResponse
     */
    protected function responseError(string $content = '', array $errors = [], int $code = Code::INTERNAL_SERVER_ERROR)
    {
        $response = [
            'status' => 'error',
            'content' => $content,
            'errors' => $errors,
        ];

        return $this->response($response, $code);
    }

    /**
     * Przygotowuje przekierowanie
     *
     * @param string $url  Adres url lub ścieżka
     * @param int    $code Kod przekierowania
     *
     * @return Redirect
     */
    protected function redirect(string $url, int $code = Code::MOVED_PERMANENTLY)
    {
        return (new Redirect($url))->setCode($code);
    }
}
