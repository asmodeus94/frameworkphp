<?php

namespace App;


use App\Cookie\Cookie;
use App\Helper\ServerHelper;

class Request
{
    /**
     * @var Request
     */
    private static $instance;

    /**
     * @var string|null
     */
    private $requestMethod = null;

    /**
     * @var string|null
     */
    private $path = null;

    /**
     * @var array|null
     */
    private $get = null;

    /**
     * @var array|null
     */
    private $post = null;

    /**
     * @var Cookie[]|null
     */
    private $cookies = null;

    const ALLOWED_HTTP_METHODS = ['GET', 'POST', 'DELETE', 'PUT'];

    /**
     * Czy żądanie pochodzi z API
     *
     * @var bool
     */
    private $api = false;

    private function __construct()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $requestMethod = strtoupper((string)$_SERVER['REQUEST_METHOD']);
            $this->requestMethod = in_array($requestMethod, self::ALLOWED_HTTP_METHODS) ? $requestMethod : null;
        }

        $this->collectData();
    }

    /**
     * @return Request
     */
    public static function getInstance(): Request
    {
        if (!isset(self::$instance)) {
            self::$instance = new Request();
        }

        return self::$instance;
    }

    /**
     * Wypełnia tablice get i post parametrami
     */
    private function collectData(): void
    {
        if (!ServerHelper::isCLI()) {
            $this->collectDataWeb();
        } else {
            $this->collectDataCli();
        }
    }

    /**
     * Zbiera dane w przypadku wejścia przez serwer WWW
     */
    private function collectDataWeb(): void
    {
        if ('POST' === $this->requestMethod && !empty($_POST)) {
            $this->post = $_POST;
        }

        if (!empty($_COOKIE)) {
            $this->cookies = [];
            foreach ($_COOKIE as $name => $content) {
                $this->cookies[$name] = new Cookie($name, $content);
            }
        }

        if (!isset($_GET)) {
            return;
        }

        $this->get = [];

        $this->path = !empty($_GET['_path_']) ? $_GET['_path_'] : null;
        if (null !== $this->path) {
            $this->api = preg_match('/^api\//', $this->path) === 1;
        }

        unset($_GET['_path_']);

        if (empty($_GET)) {
            return;
        }

        foreach ($_GET as $param => $value) {
            $this->get[$param] = $value;
        }
    }

    /**
     * Zbiera dane w przypadku wejścia poprzez skrypt (cli)
     */
    private function collectDataCli(): void
    {
        if (null === ($argument = $_SERVER['argv'][1] ?? null)) {
            return;
        }

        $this->get = [];

        if (false !== ($position = stripos($argument, '?'))) {
            $this->path = substr($argument, 0, $position);
            $length = strlen($argument);
            $parsed = [];
            if ($position + 1 < $length) {
                parse_str(substr($argument, $position + 1, $length - $position), $parsed);
            }

            foreach ($parsed as $param => $value) {
                $this->get[$param] = $value;
            }
        } else {
            $this->path = $argument;
        }
    }

    /**
     * Dodaje parametr wraz z wartością do tablicy get
     *
     * @param string $param
     * @param mixed  $value
     */
    public function appendGet(string $param, $value): void
    {
        $this->get[$param] = $value;
    }

    /**
     * @return string|null
     */
    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return array|null
     */
    public function get(): ?array
    {
        return $this->get;
    }

    /**
     * @return array|null
     */
    public function post(): ?array
    {
        return $this->post;
    }

    /**
     * @return Cookie[]|null
     */
    public function cookies(): ?array
    {
        return $this->cookies;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        if (isset($this->get[$name])) {
            return $this->get[$name];
        }

        if (isset($this->post[$name])) {
            return $this->post[$name];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isAPI(): bool
    {
        return $this->api;
    }
}
