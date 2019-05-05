<?php

namespace App;


use App\Response\Json;
use App\Response\AbstractResponse;
use App\Response\View;

abstract class Controller
{
    /**
     * @param array|View $response
     * @param int        $code
     *
     * @return AbstractResponse
     */
    protected function response($response = [], int $code = 200): AbstractResponse
    {
        if ($response instanceof View) {
            $response->setContentType('text/html');
        } else {
            if (!is_array($response) || empty($response)) {
                $response = ['status' => 'OK', 'errors' => []];
            }

            $response = new Json($response);
            $response->encode();
            $response->setContentType('application/json');
        }

        $response->setCode($code);

        return $response;
    }

    protected function redirect($url)
    {
        return new Redirect($url);
    }
}
